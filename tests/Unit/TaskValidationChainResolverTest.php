<?php

namespace Tests\Unit;

use App\Exceptions\TaskHierarchyException;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Cellule;
use App\Models\Department;
use App\Models\Fonction;
use App\Models\Localite;
use App\Models\Province;
use App\Models\Section;
use App\Services\TaskValidationChainResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskValidationChainResolverTest extends TestCase
{
    use RefreshDatabase;

    private TaskValidationChainResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(TaskValidationChainResolver::class);
    }

    public function test_cell_agent_is_validated_by_its_cell_manager(): void
    {
        [$department, $section, $cell] = $this->nationalStructure();
        $target = $this->assignNational('Agent de cellule', false, 'cellule', $department, $section, $cell);
        $manager = $this->assignNational('Chef de Cellule', true, 'cellule', $department, $section, $cell);

        $step = $this->resolver->resolve($target)[0];

        $this->assertSame('cell_manager', $step['step_code']);
        $this->assertSame($manager->id, $step['validator_agent_id']);
    }

    public function test_cell_manager_is_validated_by_its_section_manager(): void
    {
        [$department, $section, $cell] = $this->nationalStructure();
        $target = $this->assignNational('Chef de Cellule', true, 'cellule', $department, $section, $cell);
        $manager = $this->assignNational('Chef de Section', true, 'section', $department, $section);

        $step = $this->resolver->resolve($target)[0];

        $this->assertSame('section_manager', $step['step_code']);
        $this->assertSame($manager->id, $step['validator_agent_id']);
    }

    public function test_section_manager_is_validated_by_its_department_director(): void
    {
        [$department, $section, $cell] = $this->nationalStructure();
        $target = $this->assignNational('Chef de Section', true, 'section', $department, $section);
        $director = $this->assignNational('Directeur de Département', true, 'département', $department);

        $step = $this->resolver->resolve($target)[0];

        $this->assertSame('department_director', $step['step_code']);
        $this->assertSame($director->id, $step['validator_agent_id']);
    }

    public function test_department_director_is_validated_by_sen(): void
    {
        [$department] = $this->nationalStructure();
        $target = $this->assignNational('Directeur de Département', true, 'département', $department);
        $sen = $this->assignNational('Secrétaire Exécutif National', true, 'direction');

        $step = $this->resolver->resolve($target)[0];

        $this->assertSame('sen', $step['step_code']);
        $this->assertSame($sen->id, $step['validator_agent_id']);
    }

    public function test_provincial_agent_is_validated_by_sep_in_same_province(): void
    {
        $province = $this->province('Province principale');
        $otherProvince = $this->province('Province secondaire');
        $target = $this->assignTerritorial('Agent provincial', false, 'SEP', 'province', $province);
        $sep = $this->assignTerritorial('Secrétaire Exécutif Provincial', true, 'SEP', 'province', $province);
        $this->assignTerritorial('Secrétaire Exécutif Provincial autre', true, 'SEP', 'province', $otherProvince);

        $step = $this->resolver->resolve($target)[0];

        $this->assertSame('sep', $step['step_code']);
        $this->assertSame($sep->id, $step['validator_agent_id']);
    }

    public function test_local_agent_is_validated_by_sel_in_same_locality(): void
    {
        $province = $this->province('Province locale');
        $locality = Localite::create([
            'code' => 'SEL-TEST',
            'nom' => 'Localité test',
            'province_id' => $province->id,
        ]);
        $otherLocality = Localite::create([
            'code' => 'SEL-AUTRE',
            'nom' => 'Autre localité',
            'province_id' => $province->id,
        ]);
        $target = $this->assignTerritorial('Agent local', false, 'SEL', 'local', $province, $locality);
        $sel = $this->assignTerritorial('Secrétaire Exécutif Local', true, 'SEL', 'local', $province, $locality);
        $this->assignTerritorial('Secrétaire Exécutif Local autre', true, 'SEL', 'local', $province, $otherLocality);

        $step = $this->resolver->resolve($target)[0];

        $this->assertSame('sel', $step['step_code']);
        $this->assertSame($sel->id, $step['validator_agent_id']);
    }

    public function test_missing_manager_blocks_resolution(): void
    {
        [$department, $section, $cell] = $this->nationalStructure();
        $target = $this->assignNational('Agent de cellule', false, 'cellule', $department, $section, $cell);

        $this->expectException(TaskHierarchyException::class);
        $this->expectExceptionMessage('Aucun validateur cell_manager actif');

        $this->resolver->resolve($target);
    }

    public function test_duplicate_managers_block_resolution_as_ambiguous(): void
    {
        [$department, $section, $cell] = $this->nationalStructure();
        $target = $this->assignNational('Agent de cellule', false, 'cellule', $department, $section, $cell);
        $this->assignNational('Chef de Cellule A', true, 'cellule', $department, $section, $cell);
        $this->assignNational('Chef de Cellule B', true, 'cellule', $department, $section, $cell);

        try {
            $this->resolver->resolve($target);
            $this->fail('La résolution aurait dû refuser plusieurs chefs actifs.');
        } catch (TaskHierarchyException $exception) {
            $this->assertSame('TASK_HIERARCHY_AMBIGUOUS', $exception->errorCode);
        }
    }

    public function test_multiple_active_assignments_for_target_block_resolution_as_ambiguous(): void
    {
        [$department, $section, $cell] = $this->nationalStructure();
        $target = $this->assignNational('Agent de cellule', false, 'cellule', $department, $section, $cell);
        $this->assignNational('Chef de Cellule', true, 'cellule', $department, $section, $cell);
        $secondFunction = $this->function('Agent de section', 'SEN', 'section', false);

        Affectation::create([
            'agent_id' => $target->id,
            'fonction_id' => $secondFunction->id,
            'niveau_administratif' => 'SEN',
            'niveau' => 'section',
            'department_id' => $department->id,
            'section_id' => $section->id,
            'date_debut' => now(),
            'actif' => true,
        ]);

        try {
            $this->resolver->resolve($target);
            $this->fail('La résolution aurait dû refuser plusieurs affectations actives.');
        } catch (TaskHierarchyException $exception) {
            $this->assertSame('TASK_HIERARCHY_AMBIGUOUS', $exception->errorCode);
            $this->assertStringContainsString('Plusieurs affectations actives', $exception->getMessage());
        }
    }

    private function nationalStructure(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $department = Department::create([
            'code' => "DEP-{$suffix}",
            'nom' => "Département {$suffix}",
        ]);
        $section = Section::create([
            'code' => "SEC-{$suffix}",
            'nom' => "Section {$suffix}",
            'department_id' => $department->id,
        ]);
        $cell = Cellule::create([
            'code' => "CEL-{$suffix}",
            'nom' => "Cellule {$suffix}",
            'section_id' => $section->id,
        ]);

        return [$department, $section, $cell];
    }

    private function province(string $name): Province
    {
        return Province::create([
            'code' => strtoupper(bin2hex(random_bytes(3))),
            'nom' => $name . ' ' . bin2hex(random_bytes(3)),
        ]);
    }

    private function assignNational(
        string $functionName,
        bool $isManager,
        string $level,
        ?Department $department = null,
        ?Section $section = null,
        ?Cellule $cell = null,
    ): Agent {
        $agent = Agent::factory()->active()->create();
        $function = $this->function($functionName, 'SEN', $level, $isManager);

        Affectation::create([
            'agent_id' => $agent->id,
            'fonction_id' => $function->id,
            'niveau_administratif' => 'SEN',
            'niveau' => $level,
            'department_id' => $department?->id,
            'section_id' => $section?->id,
            'cellule_id' => $cell?->id,
            'date_debut' => now()->subMonth(),
            'actif' => true,
        ]);

        return $agent;
    }

    private function assignTerritorial(
        string $functionName,
        bool $isManager,
        string $administrativeLevel,
        string $level,
        Province $province,
        ?Localite $locality = null,
    ): Agent {
        $agent = Agent::factory()->active()->create([
            'province_id' => $province->id,
            'localite_id' => $locality?->id,
        ]);
        $function = $this->function($functionName, $administrativeLevel, $level, $isManager);

        Affectation::create([
            'agent_id' => $agent->id,
            'fonction_id' => $function->id,
            'niveau_administratif' => $administrativeLevel,
            'niveau' => $level,
            'province_id' => $province->id,
            'localite_id' => $locality?->id,
            'date_debut' => now()->subMonth(),
            'actif' => true,
        ]);

        return $agent;
    }

    private function function(string $name, string $administrativeLevel, string $type, bool $isManager): Fonction
    {
        return Fonction::create([
            'nom' => $name . ' ' . bin2hex(random_bytes(3)),
            'niveau_administratif' => $administrativeLevel,
            'type_poste' => $type,
            'est_chef' => $isManager,
        ]);
    }
}