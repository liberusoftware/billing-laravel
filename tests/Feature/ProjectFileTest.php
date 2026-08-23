<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Policies\ProjectFilePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_sees_only_customer_visible_files(): void
    {
        $project = Project::factory()->create();

        ProjectFile::factory()->for($project)->create(['customer_visible' => true]);
        ProjectFile::factory()->for($project)->create(['customer_visible' => false]);

        $visible = $project->files()->customerVisible()->get();

        $this->assertCount(1, $visible);
        $this->assertTrue($visible->every(fn (ProjectFile $f): bool => $f->customer_visible));
    }

    public function test_client_cannot_download_staff_only_file(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['email' => $user->email]);
        $project = Project::factory()->for($customer)->create();

        $file = ProjectFile::factory()->for($project)->create(['customer_visible' => false]);

        $this->assertFalse((new ProjectFilePolicy)->view($user, $file));
    }

    public function test_client_can_download_own_customer_visible_file(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['email' => $user->email]);
        $project = Project::factory()->for($customer)->create();

        $file = ProjectFile::factory()->for($project)->create(['customer_visible' => true]);

        $this->assertTrue((new ProjectFilePolicy)->view($user, $file));
    }

    public function test_client_cannot_download_another_customers_file(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['email' => $user->email]);
        $otherProject = Project::factory()->create();

        $file = ProjectFile::factory()->for($otherProject)->create(['customer_visible' => true]);

        $this->assertFalse((new ProjectFilePolicy)->view($user, $file));
    }
}
