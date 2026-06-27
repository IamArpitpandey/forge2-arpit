<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Organization ──────────────────────────────────────────────
        $acme = Organization::create([
            'name' => 'Acme',
            'slug' => 'acme',
        ]);

        $orgId = $acme->id;

        // ── Users (5: 1 admin, 2 agents, 2 customers) ─────────────────
        $admin = User::create([
            'organization_id' => $orgId,
            'name'  => 'Sarah Admin',
            'email' => 'admin@acme.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $agent1 = User::create([
            'organization_id' => $orgId,
            'name'  => 'Marcus Agent',
            'email' => 'marcus@acme.test',
            'password' => Hash::make('password'),
            'role' => 'agent',
        ]);

        $agent2 = User::create([
            'organization_id' => $orgId,
            'name'  => 'Diana Agent',
            'email' => 'diana@acme.test',
            'password' => Hash::make('password'),
            'role' => 'agent',
        ]);

        $customer1 = User::create([
            'organization_id' => $orgId,
            'name'  => 'John Customer',
            'email' => 'john@acme.test',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $customer2 = User::create([
            'organization_id' => $orgId,
            'name'  => 'Jane Customer',
            'email' => 'jane@acme.test',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $agents     = [$agent1->id, $agent2->id];
        $customers  = [$customer1->id, $customer2->id];
        $staff      = [$admin->id, $agent1->id, $agent2->id];

        // ── Tags (6) ──────────────────────────────────────────────────
        $tagData = ['bug', 'feature', 'billing', 'urgent', 'how-to', 'integration'];
        $tags = [];
        foreach ($tagData as $name) {
            $tag = Tag::create([
                'organization_id' => $orgId,
                'name' => $name,
            ]);
            $tags[$name] = $tag->id;
        }

        // ── Tickets (12) ──────────────────────────────────────────────
        // [subject, description, status, priority, requester, assignee, tags]
        $ticketData = [
            ['Login page throws 500 error', 'When I try to access /login on Safari, I get a 500 error. Chrome works fine.', 'open', 'urgent', $customer1->id, $agent1->id, ['bug', 'urgent']],
            ['Invoice #2847 shows wrong amount', 'The invoice I received last week shows $500 but my plan is $300/month.', 'pending', 'high', $customer2->id, $agent2->id, ['billing']],
            ['How to export data to CSV?', 'I need to export all my ticket data to CSV for reporting. Is there a way?', 'open', 'low', $customer1->id, null, ['how-to']],
            ['Feature request: dark mode', 'It would be great to have a dark mode option for the dashboard.', 'open', 'medium', $customer2->id, null, ['feature']],
            ['API rate limit too aggressive', 'We are hitting the rate limit after just 50 requests per minute. Can this be increased?', 'pending', 'high', $customer1->id, $agent1->id, ['integration', 'urgent']],
            ['Cannot reset password', 'The password reset email never arrives. I have checked spam.', 'resolved', 'medium', $customer2->id, $agent2->id, ['bug']],
            ['Slack integration broken', 'The Slack notifications stopped working after the last update.', 'open', 'high', $customer1->id, $agent1->id, ['integration', 'bug']],
            ['Upgrade plan to Enterprise', 'We want to upgrade to the Enterprise plan. Who should I talk to?', 'closed', 'low', $customer2->id, $admin->id, ['billing']],
            ['Mobile app crashes on startup', 'The iOS app crashes immediately on launch. Reinstalling did not help.', 'open', 'urgent', $customer1->id, $agent2->id, ['bug', 'urgent']],
            ['Need SSO integration guide', 'Do you have documentation for setting up SSO with Okta?', 'resolved', 'medium', $customer2->id, $agent1->id, ['how-to', 'integration']],
            ['Duplicate charges on card', 'I was charged twice this month. Please refund the duplicate.', 'pending', 'urgent', $customer1->id, $admin->id, ['billing', 'urgent']],
            ['Webhook delivery delays', 'Webhooks are arriving 5-10 minutes late, causing sync issues.', 'open', 'high', $customer2->id, $agent1->id, ['integration', 'bug']],
        ];

        $statuses = ['open', 'pending', 'resolved', 'closed'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        // Reply templates: [author_type, type, body]
        $replyTemplates = [
            ['customer', 'public',  'Can someone look into this? It is blocking our workflow.'],
            ['agent',    'public',  'Thanks for reporting. I am investigating and will get back to you shortly.'],
            ['agent',    'internal', 'Reproduced on staging. Seems related to the recent deploy — checking commit history.'],
            ['customer', 'public',  'Any update on this? It is still happening.'],
            ['agent',    'public',  'A fix has been deployed. Could you try again and let us know if it works?'],
            ['agent',    'internal', 'Patch merged in PR #482. Monitoring for regressions.'],
        ];

        foreach ($ticketData as $i => $data) {
            $ticket = Ticket::create([
                'organization_id' => $orgId,
                'subject' => $data[0],
                'description' => $data[1],
                'status' => $data[2],
                'priority' => $data[3],
                'requester_id' => $data[4],
                'assignee_id' => $data[5],
            ]);

            // Attach tags
            $tagIds = [];
            foreach ($data[6] as $tagName) {
                if (isset($tags[$tagName])) {
                    $tagIds[] = $tags[$tagName];
                }
            }
            if (!empty($tagIds)) {
                $ticket->tags()->attach($tagIds);
            }

            // Attach 2-3 replies
            $replyCount = ($i % 2 === 0) ? 3 : 2;
            $offset = ($i * 2) % count($replyTemplates);
            for ($r = 0; $r < $replyCount; $r++) {
                $template = $replyTemplates[($offset + $r) % count($replyTemplates)];
                $authorType = $template[0];

                if ($authorType === 'customer') {
                    $authorId = $data[4]; // the ticket requester
                } else {
                    // Pick the assigned agent, or a random agent
                    $authorId = $data[5] ?? $agents[$r % 2];
                }

                TicketReply::create([
                    'organization_id' => $orgId,
                    'ticket_id' => $ticket->id,
                    'author_id' => $authorId,
                    'body' => $template[2],
                    'type' => $template[1],
                ]);
            }
        }
    }
}
