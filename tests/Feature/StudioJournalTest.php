<?php

namespace Tests\Feature;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Employee;
use App\Models\Post;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudioJournalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('office'));
    }

    #[Test]
    public function only_an_admin_or_sub_admin_with_a_two_factor_session_can_open_the_studio(): void
    {
        $admin = $this->staff(Employee::GROUP_ADMIN, 'studio-admin@example.test');
        $sub = $this->staff(Employee::GROUP_SUB_ADMIN, 'studio-sub@example.test');
        $writer = $this->staff(Employee::GROUP_WRITER, 'studio-writer@example.test');
        $editor = $this->staff(Employee::GROUP_EDITOR, 'studio-editor@example.test');
        $banned = $this->staff(Employee::GROUP_ADMIN, 'studio-banned@example.test');
        $banned->is_banned = 1;
        $banned->save();

        $this->assertTrue($admin->canAccessPanel(Filament::getCurrentPanel()));
        $this->assertTrue($sub->canAccessPanel(Filament::getCurrentPanel()));
        $this->assertFalse($writer->canAccessPanel(Filament::getCurrentPanel()));
        $this->assertFalse($banned->canAccessPanel(Filament::getCurrentPanel()));

        $this->actingAs($writer, 'staff')->withSession(['office.2fa' => true, 'office.seen' => time()])
            ->get('/studio')->assertForbidden();
        $this->actingAs($editor, 'staff')->withSession(['office.2fa' => true, 'office.seen' => time()])
            ->get('/studio')->assertForbidden();
        $this->actingAs($admin, 'staff')->withSession(['office.2fa' => false])->get('/studio')->assertRedirect(route('office.challenge'));
        $this->actingAs($banned, 'staff')->withSession(['office.2fa' => true, 'office.seen' => time()])
            ->get('/studio')->assertForbidden();
        $this->actingAs($admin, 'staff')->withSession(['office.2fa' => true, 'office.seen' => time()])
            ->followingRedirects()->get('/studio')->assertOk()->assertSee('Studio office');
        $this->actingAs($sub, 'staff')->withSession(['office.2fa' => true, 'office.seen' => time()])
            ->followingRedirects()->get('/studio')->assertOk()->assertSee('Studio office');
    }

    #[Test]
    public function an_editor_creates_a_note_enforces_the_slug_and_publish_rules_then_deletes_it(): void
    {
        $admin = $this->staff(Employee::GROUP_ADMIN, 'journal-admin@example.test');
        $this->actingAs($admin, 'staff')->withSession(['office.2fa' => true, 'office.seen' => time()]);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Launch note',
                'slug' => 'launch-note',
                'excerpt' => 'What changed for existing clients.',
                'body' => 'The note itself, in markdown.',
                'author_name' => 'Studio',
                'published_at' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $draft = Post::query()->where('slug', 'launch-note')->first();
        $this->assertNotNull($draft);
        $this->assertNull($draft->published_at);
        $this->get('/blog/launch-note')->assertNotFound();

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Duplicate',
                'slug' => 'launch-note',
                'excerpt' => 'Another excerpt that is long enough.',
                'body' => 'Body.',
                'author_name' => 'Studio',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);

        Livewire::test(EditPost::class, ['record' => $draft->getKey()])
            ->fillForm([
                'published_at' => now()->addDay()->toDateTimeString(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->get('/blog/launch-note')->assertNotFound();

        Livewire::test(EditPost::class, ['record' => $draft->getKey()])
            ->fillForm([
                'title' => 'Launch note, published',
                'published_at' => now()->subHour()->toDateTimeString(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->get('/blog/launch-note')->assertOk()->assertSee('Launch note, published');

        Livewire::test(ListPosts::class)
            ->assertCanSeeTableRecords([$draft->fresh()]);

        Livewire::test(EditPost::class, ['record' => $draft->getKey()])
            ->callAction('delete');
        $this->assertNull(Post::query()->where('slug', 'launch-note')->first());
    }

    private function staff(int $group, string $email): Employee
    {
        return Employee::query()->create([
            'email' => $email,
            'employee_group' => $group,
            'first_name' => 'Studio',
            'last_name' => 'Editor',
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'is_started' => 1,
            'is_banned' => 0,
            'two_factor_enabled' => 1,
            'two_factor_type' => 'google',
            'two_factor_gsecret' => 'JBSWY3DPEHPK3PXP',
            'writer_category' => 0,
            'reg_date' => now(),
        ]);
    }
}
