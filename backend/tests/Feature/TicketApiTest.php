<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;
    private Organization $orgB;
    private User $adminA;
    private User $agentA;
    private User $customerA;
    private User $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a']);
        $this->orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b']);

        $this->adminA = User::create([
            'name' => 'Admin A',
            'email' => 'admin.a@test.local',
            'password' => Hash::make('pw'),
            'role' => 'admin',
            'organization_id' => $this->orgA->id,
        ]);

        $this->agentA = User::create([
            'name' => 'Agent A',
            'email' => 'agent.a@test.local',
            'password' => Hash::make('pw'),
            'role' => 'agent',
            'organization_id' => $this->orgA->id,
        ]);

        $this->customerA = User::create([
            'name' => 'Customer A',
            'email' => 'cust.a@test.local',
            'password' => Hash::make('pw'),
            'role' => 'customer',
            'organization_id' => $this->orgA->id,
        ]);

        $this->customerB = User::create([
            'name' => 'Customer B',
            'email' => 'cust.b@test.local',
            'password' => Hash::make('pw'),
            'role' => 'customer',
            'organization_id' => $this->orgB->id,
        ]);
    }

    // ─── Tenant Isolation ───────────────────────────────────────────

    public function test_org_a_user_cannot_fetch_org_b_ticket(): void
    {
        // Create a ticket in Org B as Org B's customer
        $this->actingAs($this->customerB);
        $ticketB = Ticket::create([
            'subject' => 'Org B Ticket',
            'description' => 'Secret stuff',
            'requester_id' => $this->customerB->id,
            'organization_id' => $this->orgB->id,
        ]);

        // Org A admin tries to fetch it
        $this->actingAs($this->adminA);
        $response = $this->getJson("/api/tickets/{$ticketB->id}");

        // Tenant scope filters it out → model not found → 404
        $response->assertNotFound();
    }

    public function test_org_a_user_does_not_see_org_b_tickets_in_list(): void
    {
        // Ticket in Org A
        $this->actingAs($this->customerA);
        Ticket::create([
            'subject' => 'My Org A Ticket',
            'description' => 'Hello',
            'requester_id' => $this->customerA->id,
            'organization_id' => $this->orgA->id,
        ]);

        // Ticket in Org B
        $this->actingAs($this->customerB);
        Ticket::create([
            'subject' => 'Org B Ticket',
            'description' => 'Hidden',
            'requester_id' => $this->customerB->id,
            'organization_id' => $this->orgB->id,
        ]);

        // Org A customer lists tickets — should only see their 1
        $this->actingAs($this->customerA);
        $response = $this->getJson('/api/tickets');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'My Org A Ticket');
    }

    // ─── CRUD Happy Path ────────────────────────────────────────────

    public function test_customer_can_create_ticket(): void
    {
        $this->actingAs($this->customerA);

        $response = $this->postJson('/api/tickets', [
            'subject' => 'Login broken',
            'description' => 'I cannot log in since yesterday.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('subject', 'Login broken')
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('priority', 'medium')
            ->assertJsonPath('organization_id', $this->orgA->id);

        $this->assertDatabaseHas('tickets', [
            'subject' => 'Login broken',
            'requester_id' => $this->customerA->id,
            'organization_id' => $this->orgA->id,
        ]);
    }

    public function test_customer_can_list_only_own_tickets(): void
    {
        $this->actingAs($this->customerA);

        // Ticket owned by customer
        Ticket::create([
            'subject' => 'Mine',
            'description' => 'My ticket',
            'requester_id' => $this->customerA->id,
            'organization_id' => $this->orgA->id,
        ]);

        // Ticket owned by admin (same org, but different requester)
        Ticket::create([
            'subject' => 'Admins',
            'description' => 'Admin ticket',
            'requester_id' => $this->adminA->id,
            'organization_id' => $this->orgA->id,
        ]);

        $response = $this->getJson('/api/tickets');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'Mine');
    }

    public function test_admin_lists_all_org_tickets(): void
    {
        $this->actingAs($this->adminA);

        Ticket::create([
            'subject' => 'Ticket 1',
            'description' => 'First',
            'requester_id' => $this->customerA->id,
            'organization_id' => $this->orgA->id,
        ]);
        Ticket::create([
            'subject' => 'Ticket 2',
            'description' => 'Second',
            'requester_id' => $this->agentA->id,
            'organization_id' => $this->orgA->id,
        ]);

        $response = $this->getJson('/api/tickets');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_customer_can_show_own_ticket(): void
    {
        $this->actingAs($this->customerA);

        $ticket = Ticket::create([
            'subject' => 'View me',
            'description' => 'Details',
            'requester_id' => $this->customerA->id,
            'organization_id' => $this->orgA->id,
        ]);

        $response = $this->getJson("/api/tickets/{$ticket->id}");

        $response->assertOk()
            ->assertJsonPath('subject', 'View me');
    }

    public function test_customer_cannot_view_other_users_ticket(): void
    {
        $this->actingAs($this->customerA);

        // Ticket created by admin in same org
        $ticket = Ticket::create([
            'subject' => 'Admin only',
            'description' => 'Private',
            'requester_id' => $this->adminA->id,
            'organization_id' => $this->orgA->id,
        ]);

        $response = $this->getJson("/api/tickets/{$ticket->id}");

        $response->assertForbidden();
    }

    public function test_customer_can_update_own_open_ticket(): void
    {
        $this->actingAs($this->customerA);

        $ticket = Ticket::create([
            'subject' => 'Original',
            'description' => 'Original desc',
            'requester_id' => $this->customerA->id,
            'organization_id' => $this->orgA->id,
        ]);

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'subject' => 'Updated subject',
        ]);

        $response->assertOk()
            ->assertJsonPath('subject', 'Updated subject');
    }

    public function test_customer_cannot_delete_ticket(): void
    {
        $this->actingAs($this->customerA);

        $ticket = Ticket::create([
            'subject' => 'Delete me',
            'description' => 'Nope',
            'requester_id' => $this->customerA->id,
            'organization_id' => $this->orgA->id,
        ]);

        $response = $this->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertForbidden();
    }

    public function test_admin_can_delete_ticket(): void
    {
        $this->actingAs($this->adminA);

        $ticket = Ticket::create([
            'subject' => 'Delete me',
            'description' => 'Bye',
            'requester_id' => $this->customerA->id,
            'organization_id' => $this->orgA->id,
        ]);

        $response = $this->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_admin_can_update_ticket_status_and_priority(): void
    {
        $this->actingAs($this->adminA);

        $ticket = Ticket::create([
            'subject' => 'Manage me',
            'description' => 'Work',
            'requester_id' => $this->customerA->id,
            'organization_id' => $this->orgA->id,
        ]);

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'status' => 'pending',
            'priority' => 'urgent',
            'assignee_id' => $this->agentA->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('priority', 'urgent')
            ->assertJsonPath('assignee_id', $this->agentA->id);
    }

    public function test_customer_cannot_set_assignee_or_status(): void
    {
        $this->actingAs($this->customerA);

        $ticket = Ticket::create([
            'subject' => 'My ticket',
            'description' => 'Mine',
            'requester_id' => $this->customerA->id,
            'organization_id' => $this->orgA->id,
        ]);

        // Customer tries to set status and assignee — validation ignores those fields
        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'subject' => 'Changed subject',
            'status' => 'closed',
            'assignee_id' => $this->agentA->id,
        ]);

        // OK (subject updated) but status/assignee unchanged
        $response->assertOk()
            ->assertJsonPath('subject', 'Changed subject')
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('assignee_id', null);
    }

    public function test_validation_requires_subject_and_description(): void
    {
        $this->actingAs($this->customerA);

        $response = $this->postJson('/api/tickets', [
            'subject' => '',
            'description' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'description']);
    }
}
