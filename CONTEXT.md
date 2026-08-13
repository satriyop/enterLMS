# EnterLMS

An academy for people who run and build Satriyo's AI product family. It is not a generic AI school and not a control plane for live agents.

## Language

**Learner**:
A person who has an Enrollment in at least one Course. Anyone who registers may become a Learner of an Open Course. An Operator, Tenant Admin, or Tenant Owner may be a Learner; those roles do not define Learner.
_Avoid_: student, operator (Operator is an Enteraksi role), user (when you mean the learning person)

**LMS Admin**:
The person who runs this academy: creates and publishes Courses and Learning Paths, grants Enrollment to Restricted Courses and Learning Paths, and grades. In this phase that is only the founder, so there is no staff member to be isolated from — see ADR 007.
_Avoid_: Operator, Tenant Admin, Content Manager, Trainer, Instructor, Compliance Officer, Auditor, Teaching Assistant (all name staff distinctions this academy does not have)

**Tenant Admin**:
An Enteraksi role. They may take Open Courses like anyone else. Restricted tenant-facing Courses come later. They never take OpenClaw administration.
_Avoid_: Tenant (when you mean a person), Operator, Family Member

**Tenant Owner**:
The commercial authority of an Enteraksi tenant. Same catalog rule as Tenant Admin.
_Avoid_: Tenant (when you mean a person), customer (when you mean this role)

**Operator**:
A staff role in Enteraksi who runs managed agent deployments. Not a role in this academy. An Operator may be a Learner here.
_Avoid_: LMS Admin, Trainer, LMS Agent

**Learner** and **LMS Admin** are the only two roles this academy models. Tenant Admin, Tenant Owner and Operator are Enteraksi roles named here so we can talk about the people; ADR 005 phases them in.

**Course**:
A collection of learning content organized into sections and lessons.
_Avoid_: materi (say Course or Lesson), lab, environment

**Open Course**:
A Course listed in the public catalog. A Learner may create their own Enrollment. v1: Pengenalan Agen AI, which is free. It introduces what an agent is and what Enteraksi is, without assuming the Learner has a tenant.
_Avoid_: public course (say Open Course), preview (that is a Lesson anyone can watch without Enrollment)

**Restricted Course**:
A Course hidden from the public catalog. LMS Admin grants Enrollment. Completing an Open Course does not grant it. v1: Administrasi Agen OpenClaw (daily Operator work).
_Avoid_: private course, invite-only (when you mean this)

**Lesson**:
A single unit of content within a Course. Forms are text, video, audio, document, YouTube, or conference — not a live agent console.
_Avoid_: materi, module (when you mean one Lesson)

**Learning Path**:
An ordered sequence of Courses for the same Learner. v1 has one: Pengenalan Agen AI, then Administrasi Agen OpenClaw. LMS Admin grants Path enrollment. The second Course stays locked until the first is complete. The Path is not publicly enrollable — otherwise finishing the Open Course would unlock OpenClaw for anyone.
_Avoid_: program, track, curriculum (when you mean the sequenced object), using a Path as a folder for unrelated Courses

**Enrollment**:
The record of a Learner being registered in a Course. Logging in does not create it. On an Open Course the Learner may create it; on a Restricted Course or Learning Path, LMS Admin grants it.
_Avoid_: registration, subscription, automatic assignment

**LMS Agent**:
An automated client that operates this academy via MCP.
_Avoid_: Agent (unqualified), OpenClaw, Hermes

**OpenClaw**:
An agent runtime. Here it is a Course subject, not a system you operate in this academy.
_Avoid_: Agent, LMS Agent

**Hermes**:
An agent runtime. Same rule as OpenClaw: a Course subject, not a system you operate here.
_Avoid_: Agent, LMS Agent

## Out of this context

**Control plane** — deploying and operating live agents belongs to Enteraksi.

**Banking / OJK / APU-PPT** — frozen; not part of this academy's language.

**Payment, SCORM, Question Bank** — built for the frozen banking scope and removed with it (ADR 007). A priced Course has no self-serve path; LMS Admin grants Enrollment.

**Tenant-facing Restricted Course** — handover for Tenant Admins (pairing, knowledge, policy of *their* tenant) is not in this phase.

**Unified Enteraksi login** — later, before Tenant Admins need Restricted Courses as Enteraksi-linked people. Public Learners register here.
