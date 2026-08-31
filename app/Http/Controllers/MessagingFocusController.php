<?php

namespace App\Http\Controllers;

use App\Domain\Tutor\Services\TutorFocusService;
use App\Http\Requests\Conversation\SetMessagingFocusRequest;
use App\Models\ChannelIdentity;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\TutorFocus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class MessagingFocusController extends Controller
{
    public function __construct(
        protected TutorFocusService $focus,
    ) {}

    public function store(SetMessagingFocusRequest $request, Course $course, Lesson $lesson, string $skin): RedirectResponse
    {
        $lesson->loadMissing('section.course');

        if ($lesson->section->course->id !== $course->id) {
            abort(404);
        }

        if (! in_array($skin, TutorFocus::skins(), true)) {
            abort(404);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $result = $this->focus->set($user, $skin, $course, $lesson);

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        $label = $skin === TutorFocus::SKIN_WHATSAPP ? 'WhatsApp' : 'Telegram';
        $linked = ChannelIdentity::query()
            ->where('user_id', $user->id)
            ->where('channel', $skin)
            ->exists();

        $message = "Focus {$label} adalah «{$lesson->title}». Percakapan di {$label} akan tercatat di Lesson ini.";

        if (! $linked) {
            return redirect()
                ->route('channels.edit')
                ->with('success', $message.' Tautkan akun '.$label.' agar pesan tercatat sebagai kamu.');
        }

        return redirect()
            ->route('courses.lessons.show', [$course, $lesson])
            ->with('success', $message);
    }
}
