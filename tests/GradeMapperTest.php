<?php

use PHPUnit\Framework\TestCase;
use UTP\Services\GradeMapper;

class GradeMapperTest extends TestCase
{
    public function testGradeToPointsSPM()
    {
        $this->assertEquals(10, GradeMapper::gradeToPoints('A+', 'SPM'));
        $this->assertEquals(0, GradeMapper::gradeToPoints('Z', 'SPM'));
    }

    public function testGradeToPointsOLevel()
    {
        $this->assertEquals(10, GradeMapper::gradeToPoints('A*', 'O-Level'));
        $this->assertEquals(5, GradeMapper::gradeToPoints('C', 'O-Level'));
    }

    public function testGetMinPassPoints()
    {
        $this->assertEquals(3, GradeMapper::getMinPassPoints('SPM'));
        $this->assertEquals(5, GradeMapper::getMinPassPoints('O-Level'));
    }

    public function testGetMaxPoints()
    {
        $this->assertEquals(10, GradeMapper::getMaxPoints('SPM'));
        $this->assertEquals(10, GradeMapper::getMaxPoints('O-Level'));
    }
}
