# Offering is the run of a Course; Facilitator owns that run

A Course is content. Parallel kelas and yearly AML batches are the same missing object: a time-bounded **Offering** with a roster. An Enrollment belongs to one Offering. Unique is Learner + Offering, so a completed run does not block the next.

A **Facilitator** is the human of record for that Offering (read Conversations, accept Grade Proposals). It is a grant on the Offering, not a third role — ADR 007 stays. LMS Admin publishes Courses and assigns the Facilitator. Until assigned, LMS Admin is that person. The Tutor remains the teacher.

Every Course has a default Offering so the `academy` preset (ADR 010) keeps today’s enroll-on-Course HTTP. Named Offerings and their HTTP surface stay behind `Academy::enabled('offerings')`. One schema: the default row exists even when the flag is off.

We rejected `organization_id`, forking Enrollment per market, and putting SKS / NIM / huruf on Course.
