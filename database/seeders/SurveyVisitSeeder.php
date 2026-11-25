<?php

namespace Database\Seeders;

use App\Models\SurveyVisit;
use Illuminate\Database\Seeder;

/** The AACCUP survey visit structures, written as the outlines the QA office already uses. */
class SurveyVisitSeeder extends Seeder
{
    private const PRELIMINARY = <<<'TXT'
Task Force/Working Committee
Area I. Vision, Mission, Goals and Objectives
A. Statement of Vision, Mission, Goals and Objectives
B. Dissemination and Acceptability
C. Congruence of Implementation
Area II. Faculty
A. Academic Qualifications and Professional Experience
B. Recruitment, Selection and Orientation
C. Faculty Adequacy and Loading
D. Rank and Tenure
E. Faculty Development
F. Professional Performance and Scholarly Works
G. Salaries, Fringe Benefits and Incentives
H. Professionalism
Area III. Curriculum and Instruction
A. Curriculum and Program of Studies
B. Instructional Processes, Methodologies and Learning Enhancement Opportunities
C. Assessment of Academic Performance
D. Classroom Management
E. Graduation Requirements
F. Administrative Support for Effective Instruction
Area IV. Support to Students
A. Student Services Program (SSP)
B. Admission and Retention
C. Guidance Program
D. Other Student Services
E. Scholarship/Grants
F. Co-curricular and Extra-curricular Programs and Activities
Area V. Research
A. Priorities and Relevance
B. Funding and Other Resources
C. Implementation, Monitoring, Evaluation and Utilization of Research Results/Outputs
D. Publication and Dissemination
Area VI. Extension and Community Involvement
A. Priorities and Relevance
B. Planning, Implementation, Monitoring and Evaluation
C. Community Involvement and Participation in the Extension Activities
Area VII. Library
A. Administration
B. Staff/Personnel
C. Collection Development, Organization and Preservation
D. Services and Utilization
E. Physical Set-up and Facilities
F. Financial Support
G. Linkages
Area VIII. Physical Plant and Facilities
A. Site
B. Campus
C. Buildings
D. Classrooms (for ocular inspection)
E. Offices, Function Rooms and Staff Rooms (for ocular inspection)
F. Assembly and Athletic Facilities (for ocular inspection)
G. Medical and Dental Clinic
H. Student Center (for ocular inspection)
I. Food Services/Canteen (for ocular inspection)
J. Accreditation Center (for ocular inspection)
K. Housing (optional)
Area IX. Laboratories
A. Laboratories/Shops/Facilities
B. Equipment and Supplies
C. Maintenance
D. Special Provisions
Area X. Administration
A. Organization
B. Academic Administration
C. Student Administration
D. Financial Management
E. Supply Management
F. Records Management
G. Institutional Planning and Development
TXT;

    private const LEVEL_I_II = <<<'TXT'
Area I. Vision, Mission, Goals and Objectives
A. Statement of Vision, Mission, Goals and Objectives
B. Dissemination and Acceptability
Area II. Faculty
A. Academic Qualifications and Professional Experience
B. Recruitment, Selection and Orientation
C. Faculty Adequacy and Loading
D. Rank and Tenure
E. Faculty Development
F. Professional Performance and Scholarly Works
G. Salaries, Fringe Benefits and Incentives
H. Professionalism
Area III. Curriculum and Instruction
A. Curriculum and Program of Studies
B. Instructional Processes, Methodologies and Learning Enhancement Opportunities
C. Assessment of Academic Performance
D. Management of Learning
E. Graduation Requirements
F. Administrative Support for Effective Instruction
Area IV. Support to Students
A. Student Services Program (SSP)
B. Student Welfare
C. Student Development
D. Institutional Student Programs and Services
E. Research, Monitoring and Evaluation
Area V. Research
A. Priorities and Relevance
B. Funding and Other Resources
C. Implementation, Monitoring, Evaluation and Utilization of Research Results/Outputs
D. Publication and Dissemination
Area VI. Extension and Community Involvement
A. Priorities and Relevance
B. Planning, Implementation, Monitoring and Evaluation
C. Funding and Other Resources
D. Community Involvement and Participation
Area VII. Library
A. Administration
B. Administrative Staff
C. Collection Development, Organization and Preservation
D. Services and Utilization
E. Physical Set-up and Facilities
F. Financial Support
G. Linkages
Area VIII. Physical Plant and Facilities
A. Campus
B. Buildings
C. Classrooms
D. Offices, Staff and Function Rooms
E. Assembly and Athletic Facilities
F. Medical and Dental Clinic
G. Student Center
H. Food Services/Canteen
I. Accreditation Center
J. Housing
Area IX. Laboratories
A. Laboratories, Shops/Facilities
B. Equipment and Supplies
C. Maintenance
D. Special Provisions
Area X. Administration
A. Organization
B. Academic Administration
C. Student Administration
D. Financial Management
E. Supply Management
F. Records Management
G. Institutional Planning and Development
H. Performance of Administrative Personnel
TXT;

    private const THIRD = <<<'TXT'
Task Force/Working Committee
Compliance
Instruction
Extension
Performance in Licensure Examination
Library
TXT;

    private const FOURTH = <<<'TXT'
Task Force/Working Committee
Compliance
Research
Performance of Graduates
Community Service
International Linkages and Consortia
Planning Process
TXT;

    public function run()
    {
        $visits = [
            ['Preliminary Survey Visit', 'PRELIMINARY', 'PRELIMINARY', 'For candidate status.', self::PRELIMINARY],
            ['1st Survey Visit', 'FIRST', 'FIRST', 'For Level I.', self::LEVEL_I_II],
            ['2nd Survey Visit', 'SECOND', 'SECOND', 'For Level II.', self::LEVEL_I_II],
            ['3rd Survey Visit', 'THIRD', 'THIRD', 'Revisit all areas.', self::THIRD],
            ['4th Survey Visit', 'FOURTH', 'FOURTH', 'Revisit all areas.', self::FOURTH],
        ];

        $start = now()->month >= 6 ? now()->year : now()->year - 1;
        $year = \App\Models\SchoolYear::firstOrCreate(['start_year' => $start], ['is_current' => true]);

        foreach ($visits as [$name, $code, $type, $description, $outline]) {
            SurveyVisit::create(compact('name', 'code', 'description') + ['visit_type' => $type, 'school_year_id' => $year->id])
                ->buildFromOutline($outline);
        }
    }
}
