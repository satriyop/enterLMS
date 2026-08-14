# Progress calculation is one class, not a strategy

Progress had three calculators behind a contract, a factory, and a config switch. Only one of them has ever run.

`ProgressCalculatorFactory::forCourse()` chose a calculator from `$course->progress_calculator_type`, falling back to `config('lms.progress_calculator')`. No migration ever created that column, so the lookup always returned `null` and always fell through to the config default. `LMS_PROGRESS_CALCULATOR` was set in no environment. Every progress figure this application has ever shown came from `AssessmentInclusiveProgressCalculator`.

The abstraction was already leaking to prove it. `ProgressTrackingService::getAssessmentStats()` guarded a call with `instanceof AssessmentInclusiveProgressCalculator` and returned empty stats "for other calculators" — a branch that could never be taken.

So the contract, the factory, the config switch, and the `LessonBased` and `Weighted` calculators are gone. The survivor is a plain class, injected concretely. Nothing about the arithmetic changed: no line of the calculation was touched, and the class the container resolved is the class it resolves now.

This matters beyond tidiness. The three calculators disagreed about a Course with no content — two returned 0%, the survivor returns 100% — and that 100% feeds completion, certificates, and Learning Path unlocking. Reconciling three implementations of a bug is three times the work of fixing one. Removing the layer first is what makes that fix tractable.

The rule this leaves behind: a strategy needs a **selector that exists**, in a migrated column or in configuration something actually sets — not merely a `match` arm in a resolver. Grading passes that test, because a Question's type picks the strategy from real data. Prerequisite evaluation passes it. Progress never did.

We rejected keeping the layer and making all three agree, keeping the contract "for future swappability" with one implementation, and migrating the missing column to make the existing design real.
