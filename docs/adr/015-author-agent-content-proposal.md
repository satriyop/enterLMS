# Author Agent proposes; LMS Admin accepts

ADR 001 named two Hermes jobs: Tutor (teach) and LMS Agent (catalog / enroll / progress). Updating existing Courses is a third job. Folding it into the Tutor lets a Learner conversation rewrite what is taught. Folding it into the LMS Agent puts Lesson write on the same token that already writes Enrollment and progress. A third login role would undo ADR 007.

The **Author Agent** is a client on its own token. v1: LMS Admin asks, on an existing Course. The Author Agent writes a **Content Proposal**. That is not the Lesson until LMS Admin accepts it. Auto-propose is not auto-publish. A Facilitator does not accept it — Course content is not an Offering grant. Scheduled mining of Conversations is later, not v1.

We rejected a third User role, `tutor.read` that can propose content, free-flow that can propose content, writing published Lessons without accept, and a scheduled Conversation scan as the first trigger.
