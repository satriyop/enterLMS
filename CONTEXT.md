# EnterLMS

An AI-first LMS. A Learner takes a Course with a Tutor; an LMS Agent may operate the academy from outside; an Author Agent may propose content changes. The public catalog may hold many free Open Courses for learning AI. It is not a control plane for live agents. See ADR 001, ADR 009, ADR 014, and ADR 015.

## Language

**Learner**:
A person who has an Enrollment in at least one Course. Anyone who registers may become a Learner of an Open Course.
_Avoid_: student, user (when you mean the learning person)

**LMS Admin**:
The person who runs this academy: creates and publishes Courses and Learning Paths, grants Enrollment onto Offerings of Restricted Courses and Learning Paths, assigns a Facilitator to an Offering, grades, and accepts Content Proposals. A Grade Proposal is not a grade until a human of record accepts it. A Content Proposal is not the Lesson until LMS Admin accepts it. Until an Offering has a Facilitator, that person is the LMS Admin. In this phase LMS Admin is only the founder — see ADR 007.
_Avoid_: Content Manager, Trainer, Instructor, Teaching Assistant (staff distinctions this academy does not have)

**Learner** and **LMS Admin** are the only two roles this academy models. Facilitator is a grant on an Offering, not a role.

**Course**:
A collection of learning content organized into sections and lessons. It may have a code (the kampus identifier for that Course). That code is not the Offering’s code.
_Avoid_: materi (say Course or Lesson), lab, environment, putting kode MK only on the Offering

**Offering**:
A time-bounded run of a Course with a roster. A Course may have many Offerings. An Enrollment belongs to one Offering. Every Course has a default Offering so an academy without named runs still enrolls. If a Course has named Offerings, Enrollment is granted onto a named Offering, not the default. The UI may say Kelas or Batch.
_Avoid_: class, section (that is CourseSection), batch, kelas, cohort (say Offering)

**Facilitator**:
The human of record for an Offering. They may grant Enrollment onto that Offering, read Conversations on it, and accept Grade Proposals on it. They need not have an Enrollment. LMS Admin assigns the grant. A Tutor is not a Facilitator.
_Avoid_: Instructor, Trainer, Dosen, PIC, Guru (those are UI labels), Tutor, teacher (when you mean the Tutor), Learner (when they have no Enrollment)

**Open Course**:
A Course listed in the public catalog. A Learner may create their own Enrollment. Open Courses are free. The catalog may list many of them; they teach AI (what an agent is, this product family, and related understanding). Pengenalan Agen AI is the first, not the only. Completing an Open Course does not grant a Restricted Course.
_Avoid_: public course (say Open Course), preview (that is a Lesson anyone can watch without Enrollment), one-intro-only (ADR 014 retired that as a catalog limit)

**Restricted Course**:
A Course hidden from the public catalog. LMS Admin or that Offering’s Facilitator grants Enrollment onto an Offering. Completing an Open Course does not grant it. v1: Administrasi Agen OpenClaw.
_Avoid_: private course, invite-only (when you mean this)

**Lesson**:
A single unit of content within a Course. Forms are text, video, audio, document, YouTube, or conference. A Tutor may talk about a Lesson on an Enrollment; a preview Lesson (watchable without Enrollment) has no Tutor. The Tutor is not itself a form, and a Lesson is not a live agent console.
_Avoid_: materi, module (when you mean one Lesson), lab, chatbot lesson

**Learning Path**:
An ordered sequence of Courses for the same Learner. v1 has one: Pengenalan Agen AI, then Administrasi Agen OpenClaw. LMS Admin grants Path enrollment. The second Course stays locked until the first is complete. The Path is not publicly enrollable — otherwise finishing the Open Course would unlock OpenClaw for anyone.
_Avoid_: program, track, curriculum (when you mean the sequenced object), using a Path as a folder for unrelated Courses

**Enrollment**:
The record of a Learner being registered in a Course on one Offering. Logging in does not create it. On an Open Course the Learner may create it on an Offering. On a Restricted Course or Learning Path, LMS Admin or that Offering’s Facilitator grants it onto an Offering. A Conversation belongs to an Enrollment; talking to a Tutor does not create one. Completing or dropping an Offering does not block Enrollment in a later Offering of the same Course.
_Avoid_: registration, subscription, automatic assignment; granting Enrollment to a Course without naming the Offering when that Course has named Offerings; granting onto the default Offering while named Offerings exist

**Tutor**:
The teacher a Learner talks to about a Lesson on their Enrollment. The Lesson overlay, WhatsApp, and Telegram are skins of the same Tutor — not a second teacher. It may talk across that Learner’s Enrollments. It does not teach a Restricted Course they were not granted, and it does not treat a later locked Lesson as if it were current (outline only, not the body). It is not an LMS Agent, not an Author Agent, and not a live console in the Lesson.
_Avoid_: chatbot, copilot, assistant, LMS Agent, Author Agent, lab, Agent (unqualified), catalog oracle (teaching Courses they are not enrolled in)

**Conversation**:
The record of a Tutor and a Learner talking about one Lesson on one Enrollment. Readable by that Learner, by LMS Admin, and by the Facilitator of that Enrollment’s Offering. New turns only while the Enrollment is active or completed — not dropped, not without an Enrollment, and not without that Lesson. A turn exists only after Laravel has recorded it — a reply that never persisted is not part of this record. A WhatsApp or Telegram thread is not this record; it is a channel that adds turns to it.
_Avoid_: chat, thread, session (when you mean this record)

**Focus**:
The Lesson (on an Enrollment) that new Tutor turns on a given skin are recorded against until the Learner switches. The overlay’s Focus is the Lesson page. WhatsApp and Telegram each have their own Focus. A first WhatsApp/Telegram Focus is the Lesson last opened in the overlay if still allowed; otherwise the Learner picks from a short list. A deep link from a Lesson page sets that messaging Focus. A Learner moves a messaging Focus only by asking to switch and Laravel accepting (enrolled, unlocked). Mentioning another Lesson does not move Focus; the Tutor may offer to switch. Outline-level talk stays on the current Focus’s Conversation.
_Avoid_: session, context, current lesson (when you mean progress)

**Assessment**:
A measure of understanding that belongs to a Course. Every Offering of that Course shares it. An Offering may set when it can be attempted. Tugas, UTS, and UAS are labels on an Assessment, not other objects. Talking to a Tutor does not complete a Lesson; understanding is still measured by Assessment.
_Avoid_: quiz (when you mean this), copying an Assessment per Offering, a separate Tugas record beside Assessment

**Grade Proposal**:
A suggested score and feedback on an Assessment answer that already requires a human of record. It is not a grade until the Facilitator of that Enrollment’s Offering accepts it — or LMS Admin, if none is assigned. It is not shown to the Learner before that, and not a turn in a Conversation.
_Avoid_: auto-grade (deterministic strategies already grade), AI grade, Tutor (when you mean this)

**Content Proposal**:
A suggested change to an existing Course’s content. It is not the Lesson until LMS Admin accepts it. The Author Agent writes it; LMS Admin asks for it. A Facilitator does not accept it.
_Avoid_: auto-update, draft Lesson (when you mean this), Tutor (when you mean this), Grade Proposal (that is Assessment)

**LMS Agent**:
A program *outside* this academy that calls MCP to catalog / enroll / progress. It is a client, like a browser. A Hermes process may be that client on a **different token** from the Tutor and from the Author Agent.
_Avoid_: Agent (unqualified), Tutor, Author Agent, calling the Tutor an LMS Agent

**Author Agent**:
A program *outside* this academy that, when LMS Admin asks, proposes a change to an existing Course. It is a client, like a browser. A Hermes process may be that client on a **different token** from the Tutor and from the LMS Agent. It does not teach, enroll, complete, or publish.
_Avoid_: role, Tutor, LMS Agent, Content Manager, editor, copilot, auto-update (when you mean publish), Agent (unqualified)

**OpenClaw**:
An agent runtime. In this academy it is a Course subject (Administrasi Agen OpenClaw). A Lesson is not a live console for it.
_Avoid_: Agent (unqualified), using OpenClaw to mean the Tutor or the LMS Agent without saying which job

**Hermes**:
An agent runtime. The Tutor is one job (its own identity and channels). An LMS Agent is another (free-flow token). An Author Agent is a third (propose content). Those stay three jobs, three tokens. It is not a Lesson form.
_Avoid_: Agent (unqualified), collapsing any two jobs into one Hermes with every tool, sharing the Tutor’s WhatsApp or Telegram with another job

A Learner takes a Course with a Tutor even if no LMS Agent or Author Agent ever connects.

## Out of this context

**Live agent operations** — deploying and operating live agents is not this academy. A Lesson is not a console for OpenClaw or Hermes.

**Payment, SCORM, Question Bank** — not this academy. A priced Course has no self-serve path; LMS Admin grants Enrollment.
