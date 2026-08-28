# EnterLMS

An AI-first LMS. A Learner takes a Course with a Tutor; an LMS Agent may operate the academy from outside. It is not a generic AI school and not a control plane for live agents. See ADR 001.

## Language

**Learner**:
A person who has an Enrollment in at least one Course. Anyone who registers may become a Learner of an Open Course.
_Avoid_: student, user (when you mean the learning person)

**LMS Admin**:
The person who runs this academy: creates and publishes Courses and Learning Paths, grants Enrollment to Restricted Courses and Learning Paths, and grades. They are the teacher of record — they may read Conversations; a Tutor does not publish; a Grade Proposal is not a grade until they accept it. In this phase that is only the founder — see ADR 007.
_Avoid_: Content Manager, Trainer, Instructor, Teaching Assistant (staff distinctions this academy does not have)

**Learner** and **LMS Admin** are the only two roles this academy models.

**Course**:
A collection of learning content organized into sections and lessons.
_Avoid_: materi (say Course or Lesson), lab, environment

**Open Course**:
A Course listed in the public catalog. A Learner may create their own Enrollment. v1: Pengenalan Agen AI, which is free. It introduces what an agent is, without assuming the Learner operates one.
_Avoid_: public course (say Open Course), preview (that is a Lesson anyone can watch without Enrollment)

**Restricted Course**:
A Course hidden from the public catalog. LMS Admin grants Enrollment. Completing an Open Course does not grant it. v1: Administrasi Agen OpenClaw.
_Avoid_: private course, invite-only (when you mean this)

**Lesson**:
A single unit of content within a Course. Forms are text, video, audio, document, YouTube, or conference. A Tutor may talk about a Lesson on an Enrollment; a preview Lesson (watchable without Enrollment) has no Tutor. The Tutor is not itself a form, and a Lesson is not a live agent console.
_Avoid_: materi, module (when you mean one Lesson), lab, chatbot lesson

**Learning Path**:
An ordered sequence of Courses for the same Learner. v1 has one: Pengenalan Agen AI, then Administrasi Agen OpenClaw. LMS Admin grants Path enrollment. The second Course stays locked until the first is complete. The Path is not publicly enrollable — otherwise finishing the Open Course would unlock OpenClaw for anyone.
_Avoid_: program, track, curriculum (when you mean the sequenced object), using a Path as a folder for unrelated Courses

**Enrollment**:
The record of a Learner being registered in a Course. Logging in does not create it. On an Open Course the Learner may create it; on a Restricted Course or Learning Path, LMS Admin grants it. A Conversation belongs to an Enrollment; talking to a Tutor does not create one.
_Avoid_: registration, subscription, automatic assignment

**Tutor**:
The teacher a Learner talks to about a Lesson on their Enrollment. It runs as a locked-down runtime invoked by this academy (Hermes tutor skill, read-only Course tools). Laravel owns the Conversation. It is not an LMS Agent and not a live console in the Lesson.
_Avoid_: chatbot, copilot, assistant, LMS Agent, lab, Agent (unqualified)

**Conversation**:
The record of a Tutor and a Learner talking about one Lesson on one Enrollment. Readable by that Learner and by LMS Admin. New turns only while the Enrollment is active or completed — not dropped, not without an Enrollment, and not without that Lesson.
_Avoid_: chat, thread, session (when you mean this record)

**Grade Proposal**:
A suggested score and feedback on an Assessment answer that already requires LMS Admin. It is not a grade until they accept it, not shown to the Learner before that, and not a turn in a Conversation.
_Avoid_: auto-grade (deterministic strategies already grade), AI grade, Tutor (when you mean this)

**LMS Agent**:
A program *outside* this academy that calls MCP to catalog / enroll / progress. It is a client, like a browser. A Hermes process may be that client on a **different token** from the Tutor.
_Avoid_: Agent (unqualified), Tutor, calling the Tutor an LMS Agent

**OpenClaw**:
An agent runtime. In this academy it is a Course subject (Administrasi Agen OpenClaw). A Lesson is not a live console for it.
_Avoid_: Agent (unqualified), using OpenClaw to mean the Tutor or the LMS Agent without saying which job

**Hermes**:
An agent runtime. It may run the Tutor (tutor skill, `tutor.read`) or an LMS Agent (free-flow token). Those are two jobs, two tokens. It is not a Lesson form.
_Avoid_: Agent (unqualified), collapsing Tutor and LMS Agent into one Hermes with every tool

A Learner takes a Course with a Tutor even if no LMS Agent ever connects.

## Out of this context

**Live agent operations** — deploying and operating live agents is not this academy. A Lesson is not a console for OpenClaw or Hermes.

**Payment, SCORM, Question Bank** — not this academy. A priced Course has no self-serve path; LMS Admin grants Enrollment.
