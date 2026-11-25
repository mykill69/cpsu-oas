<?php

namespace Tests\Unit;

use App\Support\OutlineParser;
use PHPUnit\Framework\TestCase;

class OutlineParserTest extends TestCase
{
    public function test_parses_areas_parameters_and_plain_areas()
    {
        $areas = OutlineParser::parse(<<<TXT
PRELIMINARY SURVEY VISIT (for Candidate)

Area I. Vision, Mission, Goals and Objectives
Parameters
A.\tStatement of Vision, Mission, Goals and Objectives
B.\tDissemination and Acceptability
Area VIII. Physical Plant and Facilities
Parameters
D.\tCampus
E.\tBuildings
Task Force/Working Committee
Compliance
Areas:
Instruction
TXT);

        $this->assertCount(5, $areas);
        $this->assertSame('Area I. Vision, Mission, Goals and Objectives', $areas[0]['name']);
        $this->assertSame('AREA_I', $areas[0]['code']);
        $this->assertSame(['Statement of Vision, Mission, Goals and Objectives', 'Dissemination and Acceptability'], $areas[0]['parameters']);
        $this->assertSame(['Campus', 'Buildings'], $areas[1]['parameters']);
        $this->assertSame('TASK_FORCE_WORKING_COMMITTEE', $areas[2]['code']);
        $this->assertSame([], $areas[3]['parameters']);
        $this->assertSame('Instruction', $areas[4]['name']);
    }

    public function test_letters()
    {
        $this->assertSame('A', OutlineParser::letter(0));
        $this->assertSame('K', OutlineParser::letter(10));
        $this->assertSame('AA', OutlineParser::letter(26));
    }
}
