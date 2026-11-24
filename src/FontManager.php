<?php

declare(strict_types=1);

namespace App;

/**
 * Manages the pool of fonts available to every visitor of the app:
 *  - a handful of built-in, open-source, metric-compatible fonts (always present)
 *  - any custom TTF/OTF fonts uploaded by users, persisted to disk and shared
 *    with everyone (per the app's "open font library" design), tracked in a
 *    small JSON index file next to the font files.
 */
final class FontManager
{
    private string $storageDir;
    private string $indexFile;

    public function __construct()
    {
        $this->storageDir = Config::fontsStoragePath();
        $this->indexFile  = $this->storageDir . '/index.json';
    }

    /**
     * Full list of fonts selectable in the UI. `scripts` tells the picker which
     * writing systems the font can draw (see FontScripts), so it can show a
     * sample in the font's own language.
     *
     * @return array<int, array{key:string,name:string,custom:bool,scripts:list<string>,v:string}>
     */
    public function listFonts(): array
    {
        $fonts = [];
        foreach (Config::builtInFonts() as $label => $key) {
            $path = $this->builtInPath($key);
            $fonts[] = [
                'key' => $key, 'name' => $label, 'custom' => false,
                'scripts' => $path !== null ? FontScripts::detect($path) : ['latin'],
                'v' => '1',
            ];
        }
        foreach ($this->readIndex() as $entry) {
            $file = (string) ($entry['files']['regular'] ?? '');
            $path = $file !== '' ? $this->storageDir . '/' . basename($file) : '';
            $fonts[] = [
                'key' => (string) $entry['key'], 'name' => (string) $entry['name'], 'custom' => true,
                'scripts' => $path !== '' && is_file($path) ? FontScripts::detect($path) : [],
                // Changes when the file is replaced, so browsers do not keep an old preview.
                'v' => $path !== '' && is_file($path) ? (string) filemtime($path) : '0',
            ];
        }
        return $fonts;
    }

    /**
     * Absolute path of a font's file for the browser preview (regular style by default).
     * Null when the font or variant does not exist.
     */
    public function webFile(string $key, string $variant = 'regular'): ?string
    {
        if (isset(Config::builtInFontFiles()[$key])) {
            return $variant === 'regular' ? $this->builtInPath($key) : null;
        }
        foreach ($this->readIndex() as $entry) {
            if (($entry['key'] ?? null) === $key) {
                $file = $entry['files'][$variant] ?? null;
                $path = is_string($file) ? $this->storageDir . '/' . basename($file) : '';
                return $path !== '' && is_file($path) ? $path : null;
            }
        }
        return null;
    }

    private function builtInPath(string $key): ?string
    {
        $file = Config::builtInFontFiles()[$key] ?? null;
        if ($file === null) {
            return null;
        }
        $dirs = array_merge([Config::assetsFontsPath()], (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir']);
        foreach ($dirs as $dir) {
            $path = rtrim((string) $dir, '/') . '/' . $file;
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Validate + store an uploaded font family (1-4 files: regular required,
     * bold/italic/bolditalic optional) and register it in the shared index.
     *
     * @param array<string, array{tmp_name:string,size:int,error:int,name:string}|null> $files
     *        keys: regular, bold, italic, bolditalic
     * @return array{key:string,name:string} the newly registered font
     * @throws \RuntimeException on validation failure
     */
    public function registerUpload(string $familyName, array $files): array
    {
        $familyName = trim(preg_replace('/\s+/', ' ', $familyName) ?? '');
        if ($familyName === '' || mb_strlen($familyName) > 60) {
            throw new \RuntimeException('Please provide a font name (1-60 characters).');
        }
        if (empty($files['regular'])) {
            throw new \RuntimeException('A "Regular" font file is required.');
        }

        if (count($this->readIndex()) >= Config::maxCustomFonts()) {
            throw new \RuntimeException('The shared font library is full. Please ask the site admin to remove unused fonts.');
        }

        $key = $this->slugify($familyName);
        if ($key === '' || in_array($key, ['sans', 'serif', 'mono', 'amiri'], true)) {
            throw new \RuntimeException('That font name is reserved or invalid. Please choose another.');
        }

        // De-duplicate: if already exists, suffix with a short counter.
        $index = $this->readIndex();
        // Built-in keys (oldenglish, gandhiserif, alata, ...) are taken too:
        // otherwise uploading a font called e.g. "Alata" would silently
        // override the built-in one in the mPDF font table. mPDF's own
        // families (dejavusans, freeserif, courier, ...) are protected too.
        $mpdfKeys = array_keys((new \Mpdf\Config\FontVariables())->getDefaults()['fontdata']);
        $existingKeys = array_merge(array_column($index, 'key'), array_values(Config::builtInFonts()), ['amiri'], $mpdfKeys);
        $baseKey = $key;
        $i = 2;
        while (in_array($key, $existingKeys, true)) {
            $key = $baseKey . '-' . $i;
            $i++;
        }

        $stored = [];
        try {
            foreach (['regular', 'bold', 'italic', 'bolditalic'] as $variant) {
                if (empty($files[$variant])) {
                    continue;
                }
                $stored[$variant] = $this->storeVariant($files[$variant], $key, $variant);
            }
        } catch (\RuntimeException $e) {
            // Don't leave orphaned files behind when a later variant is rejected.
            foreach ($stored as $storedFile) {
                @unlink($this->storageDir . '/' . $storedFile);
            }
            throw $e;
        }

        // A file that only *starts* like a font but cannot be read would make every cover that
        // uses it fail, so the regular file is opened and its character table checked now.
        if (!FontScripts::isReadable($this->storageDir . '/' . $stored['regular'])) {
            foreach ($stored as $storedFile) {
                @unlink($this->storageDir . '/' . $storedFile);
            }
            throw new \RuntimeException('That file could not be read as a TTF/OTF font (TrueType collections .ttc are not supported).');
        }

        $entry = [
            'key'  => $key,
            'name' => $familyName,
            'files' => $stored,
        ];
        $index[] = $entry;
        $this->writeIndex($index);

        return ['key' => $key, 'name' => $familyName];
    }

    /**
     * Custom (uploaded) fonts with their file names, for the admin panel.
     * @return array<int, array{key:string,name:string,files:array<string,string>}>
     */
    public function listCustom(): array
    {
        $out = [];
        foreach ($this->readIndex() as $entry) {
            $out[] = [
                'key'   => (string) ($entry['key'] ?? ''),
                'name'  => (string) ($entry['name'] ?? ''),
                'files' => is_array($entry['files'] ?? null) ? $entry['files'] : [],
            ];
        }
        return $out;
    }

    /** Removes an uploaded font and its files. Returns false if there is no such font. */
    public function deleteFont(string $key): bool
    {
        $kept = [];
        $found = false;
        foreach ($this->readIndex() as $entry) {
            if (($entry['key'] ?? '') === $key) {
                $found = true;
                foreach (($entry['files'] ?? []) as $file) {
                    $path = $this->storageDir . '/' . basename((string) $file);
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
                continue;
            }
            $kept[] = $entry;
        }
        if ($found) {
            $this->writeIndex($kept);
        }
        return $found;
    }

    /**
     * Build the fontDir / fontdata arrays mPDF needs, merging defaults with
     * the bundled Amiri font and any custom uploaded fonts.
     */
    public function buildMpdfFontConfig(bool $shaping = true): array
    {
        $defaultFontDirs   = (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'];
        $defaultFontData   = (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'];

        $fontDirs = array_merge($defaultFontDirs, [
            Config::assetsFontsPath(),
            $this->storageDir,
        ]);

        $fontData = $defaultFontData;
        $fontData['amiri'] = ['R' => 'Amiri-Regular.ttf'];
        $fontData['oldenglish'] = ['R' => 'oldenglishtextmt.ttf'];
        $fontData['gandhiserif'] = [
            'R'  => 'GandhiSerif-Regular.ttf',
            'B'  => 'GandhiSerif-Bold.ttf',
            'I'  => 'GandhiSerif-Italic.ttf',
            'BI' => 'GandhiSerif-BoldItalic.ttf',
        ];
        $fontData['alata'] = ['R' => 'Alata-Regular.ttf'];

        foreach ($this->readIndex() as $entry) {
            $variants = [];
            foreach ($entry['files'] ?? [] as $variant => $filename) {
                $variants[match ($variant) {
                    'regular'    => 'R',
                    'bold'       => 'B',
                    'italic'     => 'I',
                    'bolditalic' => 'BI',
                    default      => 'R',
                }] = $filename;
            }
            if (!empty($variants)) {
                // Bangla, Arabic, Devanagari ... only look right with OpenType shaping: without it
                // vowel signs land on the wrong side and conjuncts fall apart.
                $regular = $this->storageDir . '/' . basename((string) ($entry['files']['regular'] ?? ''));
                if ($shaping && is_file($regular) && FontScripts::needsShaping(FontScripts::detect($regular))) {
                    $variants['useOTL'] = 0xFF;
                }
                $fontData[$entry['key']] = $variants;
            }
        }

        return ['fontDir' => $fontDirs, 'fontdata' => $fontData];
    }

    /** Resolve a requested font key to a safe, known mPDF font family key. */
    public function resolveFontKey(string $requested): string
    {
        $requested = strtolower(trim($requested));
        $known = array_column($this->listFonts(), 'key');
        return in_array($requested, $known, true) ? $requested : 'sans';
    }

    /**
     * A per-field font choice: the font key, or null when the field should use
     * its default font group (blank, unknown or since-deleted font).
     */
    public function resolveOptionalKey(mixed $requested): ?string
    {
        $requested = is_string($requested) ? strtolower(trim($requested)) : '';
        if ($requested === '') {
            return null;
        }
        $known = array_column($this->listFonts(), 'key');
        return in_array($requested, $known, true) ? $requested : null;
    }

    // ---- internals ---------------------------------------------------

    private function storeVariant(array $file, string $key, string $variant): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("Upload error on the \"$variant\" file.");
        }
        if (($file['size'] ?? 0) > Config::maxFontUploadBytes()) {
            throw new \RuntimeException("The \"$variant\" file is larger than 2 MB.");
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, Config::allowedFontExtensions(), true)) {
            throw new \RuntimeException("The \"$variant\" file must be a .ttf or .otf font.");
        }
        // Light sanity check: TTF/OTF files start with a known sfnt signature.
        $head = @file_get_contents($file['tmp_name'], false, null, 0, 4);
        $validSignatures = ["\x00\x01\x00\x00", 'OTTO', 'true', 'ttcf'];
        if ($head === false || !in_array($head, $validSignatures, true)) {
            throw new \RuntimeException("The \"$variant\" file doesn't look like a valid font.");
        }

        $filename = $key . '-' . $variant . '.' . $ext;
        $dest = $this->storageDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest) && !@copy($file['tmp_name'], $dest)) {
            throw new \RuntimeException("Could not save the \"$variant\" font file.");
        }
        @chmod($dest, 0644);

        return $filename;
    }

    private function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '', $text) ?? '';
        return substr($text, 0, 24);
    }

    private function readIndex(): array
    {
        if (!is_file($this->indexFile)) {
            return [];
        }
        $json = @file_get_contents($this->indexFile);
        $data = json_decode((string) $json, true);
        return is_array($data) ? $data : [];
    }

    private function writeIndex(array $index): void
    {
        // A simple lock guards against two simultaneous uploads corrupting the file.
        $fp = fopen($this->indexFile, 'c+');
        if ($fp === false) {
            throw new \RuntimeException('Could not write the font index.');
        }
        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        fwrite($fp, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
