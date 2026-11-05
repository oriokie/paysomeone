<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApplyController extends Controller
{
    public function create(): View
    {
        return view('pages.apply', ['questions' => $this->questions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:prox_employee,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'sample' => ['required', 'file', 'max:5120'],
            'answers' => ['required', 'array'],
        ]);
        $score = 0;
        foreach ($this->questions() as $index => $question) {
            if (($data['answers'][$index] ?? null) === $question['answer']) {
                $score++;
            }
        }
        if ($score < 3) {
            return back()->withInput()->withErrors(['answers' => 'The writing check needs at least 3 of 4. Nothing was saved.']);
        }

        $person = new Employee;
        $person->email = strtolower($data['email']);
        $person->first_name = $data['first_name'];
        $person->last_name = $data['last_name'];
        $person->employee_group = 5;
        $person->password = Hash::make($data['password']);
        $person->secure_key = md5($data['email']);
        $person->is_started = 0;
        $person->is_banned = 0;
        $person->is_application_completed = 1;
        $person->is_samples_complete = 1;
        $person->writing_prompt_answer = 'score '.$score.'/4';
        $person->is_writingprompt_complete = 1;
        $person->reg_date = now();
        $person->save();

        $upload = $data['sample'];
        $name = Str::lower(Str::random(8)).'_'.preg_replace('/[^a-zA-Z0-9._-]/', '', $upload->getClientOriginalName());
        $path = 'applications/'.$person->employee_id.'/'.$name;
        \Illuminate\Support\Facades\Storage::disk('order_files')->put($path, file_get_contents($upload->getRealPath()) ?: '');
        $file = new EmployeeFile;
        $file->employee_id = $person->employee_id;
        $file->uploader_id = $person->employee_id;
        $file->handle = 'application-sample';
        $file->description = 'Application sample';
        $file->name = $upload->getClientOriginalName();
        $file->size = $upload->getSize() ?: 0;
        $file->source = $path;
        $file->extension = $upload->getClientOriginalExtension();
        $file->date_add = now();
        $file->date_upd = now();
        $file->save();

        return redirect()->route('apply')->with('status', 'Application received. An editor will review the sample. You cannot sign in until it is approved.');
    }

    /**
     * @return list<array{prompt: string, choices: list<string>, answer: string}>
     */
    private function questions(): array
    {
        return [
            ['prompt' => 'Which sentence is grammatical?', 'choices' => ['The report were sent.', 'The report was sent.'], 'answer' => 'The report was sent.'],
            ['prompt' => 'Which is the clearer business line?', 'choices' => ['We will send the draft on Friday.', 'The draft will be in a position to be sent.'], 'answer' => 'We will send the draft on Friday.'],
            ['prompt' => 'Choose the right word.', 'choices' => ['Their deadline is Friday.', 'There deadline is Friday.'], 'answer' => 'Their deadline is Friday.'],
            ['prompt' => 'Which avoids a comma splice?', 'choices' => ['The brief is clear, we can start.', 'The brief is clear. We can start.'], 'answer' => 'The brief is clear. We can start.'],
        ];
    }
}
