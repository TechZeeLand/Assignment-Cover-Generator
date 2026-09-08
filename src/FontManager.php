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
     * Full list of fonts selectable in the UI.
     * @return array<int, array{key:string,name:string,custom:bool}>
     */
    public function listFonts(): array
    {
        $fonts = [];
        foreach (Config::builtInFonts() as $label => $key) {
            $fonts[] = ['key' => $key, 'name' => $label, 'custom' => false];
        }
        foreach ($this->readIndex() as $entry) {
            $fonts[] = ['key' => $entry['key'], 'name' => $entry['name'], 'custom' => true];
        }
        return $fonts;
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

        $key = $this->slugify($familyName);
        if ($key === '' || in_array($key, ['sans', 'serif', 'mono', 'amiri'], true)) {
            throw new \RuntimeException('That font name is reserved or invalid. Please choose another.');
        }

        // De-duplicate: if already exists, suffix with a short counter.
        $index = $this->readIndex();
        $existingKeys = array_column($index, 'key');
        $baseKey = $key;
        $i = 2;
        while (in_array($key, $existingKeys, true)) {
            $key = $baseKey . '-' . $i;
            $i++;
        }

        $stored = [];
        foreach (['regular', 'bold', 'italic', 'bolditalic'] as $variant) {
            if (empty($files[$variant])) {
                continue;
            }
            $stored[$variant] = $this->storeVariant($files[$variant], $key, $variant);
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
     * Build the fontDir / fontdata arrays mPDF needs, merging defaults with
     * the bundled Amiri font and any custom uploaded fonts.
     */
    public function buildMpdfFontConfig(): array
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
            'R'  => 'GandhiSerif-Regular.otf',
            'B'  => 'GandhiSerif-Bold.otf',
            'I'  => 'GandhiSerif-Italic.otf',
            'BI' => 'GandhiSerif-BoldItalic.otf',
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
