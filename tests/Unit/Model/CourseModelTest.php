<?php

namespace tests\Unit\Model;

use app\model\CourseModel;
use PHPUnit\Framework\TestCase;

class CourseModelTest extends TestCase
{
    private function makeValidCourse(): CourseModel
    {
        $course = new CourseModel();
        $course->courseTitle  = 'Intro to PHP';
        $course->courseDesc   = 'A beginner PHP course.';
        $course->startDate    = '2025-09-01 09:00:00';
        $course->endDate      = '2025-09-01 17:00:00';
        $course->maxAttendees = '20';
        $course->lecturer     = 'some-uid';
        return $course;
    }

    public function test_tableName_returns_courses(): void
    {
        $this->assertSame('courses', CourseModel::tableName());
    }

    public function test_attributes_list_is_correct(): void
    {
        $expected = ['uid', 'courseTitle', 'startDate', 'endDate', 'maxAttendees', 'courseDesc', 'lecturer'];
        $this->assertSame($expected, CourseModel::attributes());
    }

    // getCoursesByUids returns early before hitting the DB if the array is empty
    // so this is safe to test without a stub
    public function test_getCoursesByUids_with_empty_array_returns_empty(): void
    {
        $course = new CourseModel();
        $this->assertSame([], $course->getCoursesByUids([]));
    }

    // required field checks
    public function test_missing_title_fails(): void
    {
        $course = $this->makeValidCourse();
        $course->courseTitle = '';
        $course->validate();
        $this->assertArrayHasKey('courseTitle', $course->errors);
    }

    public function test_missing_description_fails(): void
    {
        $course = $this->makeValidCourse();
        $course->courseDesc = '';
        $course->validate();
        $this->assertArrayHasKey('courseDesc', $course->errors);
    }

    public function test_missing_start_date_fails(): void
    {
        $course = $this->makeValidCourse();
        $course->startDate = '';
        $course->validate();
        $this->assertArrayHasKey('startDate', $course->errors);
    }

    public function test_missing_end_date_fails(): void
    {
        $course = $this->makeValidCourse();
        $course->endDate = '';
        $course->validate();
        $this->assertArrayHasKey('endDate', $course->errors);
    }

    public function test_missing_lecturer_fails(): void
    {
        $course = $this->makeValidCourse();
        $course->lecturer = '';
        $course->validate();
        $this->assertArrayHasKey('lecturer', $course->errors);
    }

    // title has a max of 32 chars
    public function test_title_over_32_chars_fails(): void
    {
        $course = $this->makeValidCourse();
        $course->courseTitle = str_repeat('a', 33);
        $course->validate();
        $this->assertArrayHasKey('courseTitle', $course->errors);
    }

    public function test_title_at_exactly_32_chars_passes(): void
    {
        $course = $this->makeValidCourse();
        $course->courseTitle = str_repeat('a', 32);
        $course->validate();
        $this->assertArrayNotHasKey('courseTitle', $course->errors);
    }

    // desc has a max of 255 chars
    public function test_desc_over_255_chars_fails(): void
    {
        $course = $this->makeValidCourse();
        $course->courseDesc = str_repeat('a', 256);
        $course->validate();
        $this->assertArrayHasKey('courseDesc', $course->errors);
    }

    public function test_desc_at_exactly_255_chars_passes(): void
    {
        $course = $this->makeValidCourse();
        $course->courseDesc = str_repeat('a', 255);
        $course->validate();
        $this->assertArrayNotHasKey('courseDesc', $course->errors);
    }

    public function test_valid_course_passes_validation(): void
    {
        $course = $this->makeValidCourse();
        $this->assertTrue($course->validate());
    }

    public function test_invalid_course_returns_false(): void
    {
        $course = $this->makeValidCourse();
        $course->courseTitle = '';
        $this->assertFalse($course->validate());
    }
}