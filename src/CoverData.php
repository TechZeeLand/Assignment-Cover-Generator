<?php

declare(strict_types=1);

namespace App;

/**
 * A sanitized, validated representation of everything needed to render one
 * assignment cover — shared by the mPDF renderer (CoverBuilder) so the
 * live browser preview and the generated PDF are always built from the
 * exact same data.
 */
final class CoverData
{
    public bool $showBorder;

    public string $versityFont;
    public string $primaryFont;
    public string $secondaryFont;

    public string $primaryColor;
    public string $secondaryColor;
    public string $accentColor; // "title border color", falls back to primaryColor

    public string $headerSuffix;

    public bool $showBismillah;

    public bool $showVersityName;
    public string $versityName;

    public bool $showDeptName;
    public string $deptName;

    public bool $showStudentName;
    public string $studentName;

    public bool $showStudentId;
    public string $studentId;

    public bool $showStudentSection;
    public string $studentSection;

    public bool $showStudentBatch;
    public string $studentBatch;

    public bool $showStudentProgram;
    public string $studentProgram;

    public string $semesterType; // "Semester" or "Trimester"
    public bool $showSemester;
    public string $semester;

    public bool $showCourseCode;
    public string $courseCode;

    public bool $showCourseTitle;
    public string $courseTitleHtml;

    public bool $showCourseTeacherName;
    public string $courseTeacherName;

    public bool $showCourseTeacherDesignation;
    public string $courseTeacherDesignation;

    public bool $showTopic;
    public string $topicHtml;

    public bool $showSubmissionDate;
    public string $submissionDateDisplay;

    public static function fromRequest(array $data, FontManager $fonts): self
    {
        $c = new self();

        $c->showBorder = Sanitize::bool($data['border'] ?? null);

        $c->versityFont   = $fonts->resolveFontKey((string) ($data['versity-name-font'] ?? 'sans'));
        $c->primaryFont   = $fonts->resolveFontKey((string) ($data['primary-font'] ?? 'sans'));
        $c->secondaryFont = $fonts->resolveFontKey((string) ($data['secondary-font'] ?? 'sans'));

        $c->primaryColor   = Sanitize::color($data['primary-color'] ?? null, '#000000');
        $c->secondaryColor = Sanitize::color($data['secondary-color'] ?? null, '#000000');

        $useAccent = Sanitize::bool($data['use-title-border-color'] ?? null);
        $c->accentColor = $useAccent
            ? Sanitize::color($data['title-border-color'] ?? null, $c->primaryColor)
            : $c->primaryColor;

        $suffix = trim((string) ($data['header-suffix'] ?? ''));
        $c->headerSuffix = Sanitize::text($suffix === '' ? '---' : $suffix, 10);

        $c->showBismillah = Sanitize::bool($data['bismillah'] ?? null);

        $c->showVersityName = Sanitize::bool($data['show-versity-name'] ?? null);
        $c->versityName     = Sanitize::text($data['versity'] ?? '', 120);

        $c->showDeptName = Sanitize::bool($data['show-dept-name'] ?? null);
        $c->deptName     = Sanitize::text($data['dept-name'] ?? '', 120);

        $c->showStudentName = Sanitize::bool($data['show-student-name'] ?? null);
        $c->studentName     = Sanitize::text($data['student-name'] ?? '', 80);

        $c->showStudentId = Sanitize::bool($data['show-student-id'] ?? null);
        $c->studentId     = Sanitize::text($data['student-id'] ?? '', 40);

        $c->showStudentSection = Sanitize::bool($data['show-student-section'] ?? null);
        $c->studentSection     = Sanitize::text($data['student-section'] ?? '', 40);

        $c->showStudentBatch = Sanitize::bool($data['show-student-batch'] ?? null);
        $c->studentBatch     = Sanitize::text($data['student-batch'] ?? '', 40);

        $c->showStudentProgram = Sanitize::bool($data['show-student-program'] ?? null);
        $c->studentProgram     = Sanitize::text($data['student-program'] ?? '', 60);

        $semType = (string) ($data['semester-type'] ?? 'Semester');
        $c->semesterType = $semType === 'Trimester' ? 'Trimester' : 'Semester';

        $c->showSemester = Sanitize::bool($data['show-semester'] ?? null);
        $c->semester     = Sanitize::text($data['semester'] ?? '', 40);

        $c->showCourseCode = Sanitize::bool($data['show-course-code'] ?? null);
        $c->courseCode     = Sanitize::text($data['course-code'] ?? '', 40);

        $c->showCourseTitle   = Sanitize::bool($data['show-course-title'] ?? null);
        $c->courseTitleHtml   = Sanitize::richText($data['course-title-html'] ?? ($data['course-title'] ?? ''), 300);

        $c->showCourseTeacherName = Sanitize::bool($data['show-course-teacher-name'] ?? null);
        $c->courseTeacherName     = Sanitize::text($data['course-teacher-name'] ?? '', 80);

        $c->showCourseTeacherDesignation = Sanitize::bool($data['show-course-teacher-designation'] ?? null);
        $c->courseTeacherDesignation     = Sanitize::text($data['course-teacher-designation'] ?? '', 120);

        $c->showTopic     = Sanitize::bool($data['show-topic'] ?? null);
        $c->topicHtml     = Sanitize::richText($data['topic-html'] ?? ($data['topic'] ?? ''), 400);

        $c->showSubmissionDate = Sanitize::bool($data['show-submission-date'] ?? null);
        $c->submissionDateDisplay = self::formatDate((string) ($data['submission-date'] ?? ''));

        return $c;
    }

    private static function formatDate(string $isoDate): string
    {
        if ($isoDate === '') {
            return '';
        }
        $ts = strtotime($isoDate);
        if ($ts === false) {
            return Sanitize::text($isoDate, 40);
        }
        return date('F d, Y', $ts);
    }
}
