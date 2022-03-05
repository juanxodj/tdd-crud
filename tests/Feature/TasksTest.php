<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;

class TasksTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function a_user_can_read_all_the_tasks()
    {
        //Given we have task in the database
        $task = Task::factory()->create();

        //When user visit the tasks page
        $response = $this->get('/tasks');

        //He should be able to read the task
        $response->assertSee($task->title);
    }

    /** @test */
    public function a_user_can_read_single_task()
    {
        //Given we have task in the database
        $task = Task::factory()->create();
        //When user visit the task's URI
        $response = $this->get('/tasks/' . $task->id);
        //He can see the task details
        $response->assertSee($task->title)
            ->assertSee($task->description);
    }

    /** @test */
    public function authenticated_users_can_create_a_new_task()
    {
        $user = User::factory()->create();
        //Given we have an authenticated user
        $this->actingAs($user);
        //And a task object
        $task = Task::factory()->make();
        //When user submits post request to create task endpoint
        $this->post('/tasks/create', $task->toArray());
        //It gets stored in the database
        $this->assertEquals(1, Task::all()->count());
    }

    /** @test */
    public function unauthenticated_users_cannot_create_a_new_task()
    {
        //Given we have a task object
        $task = Task::factory()->make();
        //When unauthenticated user submits post request to create task endpoint
        // He should be redirected to login page
        $this->post('/tasks/create', $task->toArray())
            ->assertRedirect('/login');
    }

    /** @test */
    public function a_task_requires_a_title()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->make(['title' => null]);

        $this->post('/tasks/create', $task->toArray())
            ->assertSessionHasErrors('title');
    }

    /** @test */
    public function a_task_requires_a_description()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $task = Task::factory()->make(['description' => null]);

        $this->post('/tasks/create', $task->toArray())
            ->assertSessionHasErrors('description');
    }

    /** @test */
    public function authorized_user_can_update_the_task()
    {
        $user = User::factory()->create();
        //Given we have a signed in user
        $this->actingAs($user);
        //And a task which is created by the user
        $task = Task::factory()->create(['user_id' => Auth::id()]);
        $task->title = "Updated Title";
        //When the user hit's the endpoint to update the task
        $this->put('/tasks/' . $task->id, $task->toArray());
        //The task should be updated in the database.
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Updated Title']);
    }

    /** @test */
    public function unauthorized_user_cannot_update_the_task()
    {
        $user = User::factory()->create();
        //Given we have a signed in user
        $this->actingAs($user);
        //And a task which is not created by the user
        $task = Task::factory()->create();
        $task->title = "Updated Title";
        //When the user hit's the endpoint to update the task
        $response = $this->put('/tasks/' . $task->id, $task->toArray());
        //We should expect a 403 error
        $response->assertStatus(403);
    }

    /** @test */
    public function authorized_user_can_delete_the_task()
    {
        $user = User::factory()->create();
        //Given we have a signed in user
        $this->actingAs($user);
        //And a task which is created by the user
        $task = Task::factory()->create(['user_id' => Auth::id()]);
        //When the user hit's the endpoint to delete the task
        $this->delete('/tasks/' . $task->id);
        //The task should be deleted from the database.
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function unauthorized_user_cannot_delete_the_task()
    {
        $user = User::factory()->create();
        //Given we have a signed in user
        $this->actingAs($user);
        //And a task which is not created by the user
        $task = Task::factory()->create();
        //When the user hit's the endpoint to delete the task
        $response = $this->delete('/tasks/' . $task->id);
        //We should expect a 403 error
        $response->assertStatus(403);
    }
}
