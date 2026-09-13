---
name: test-design
description: >
  Load before writing, changing, or deleting tests, before adding a mock or
  test helper, and when deciding whether a change needs a test at all. Which
  tests earn their place, what to assert, the test-double ladder,
  characterisation tests before refactoring, property-based tests, and the
  mutation check.
---

# test-design

Tests carry the What: each one pins an observable contract a consumer relies
on. A test that cannot fail on a plausible bug is maintenance without
protection; a missing test on a real boundary is a bug waiting for a
reviewer. This skill decides which tests to write and how. The repository's
runner, layout, naming, and the size of neighbouring tests decide the rest:
read two neighbouring test files before writing one.

## Which tests a change needs

| Change | Test |
| --- | --- |
| Bug fix | One reproduction that fails before the fix and passes after. Check sibling callers of the fixed function; if they had the same bug, the reproduction covers them or gets a row. |
| New behaviour | One test per observable contract: the happy path, each boundary (empty, one, max, off-by-one), each error the caller must handle. |
| Refactoring | No new tests. If the code has none, characterisation tests first (below), then refactor, then change behaviour in a separate commit. |
| Public interface change | Existing tests updated to the new contract; a migration-path test only if consumers can hit both shapes at once. |
| Script, hook, skill, config | Run it against controlled input and assert output, exit code, or side effect. Never assert that the source contains a line. |
| Trivial forwarding, constants, getters, wiring | None. Assert the first consumer-visible result that depends on them instead. |
| Prose for humans | None. |

Scratch checks (a throwaway script proving a change) need not be kept. Keep
a test when the repository keeps tests for that kind of change, sized like
its neighbours.

## Name the break before writing the body

State the production change that would make this test fail, then classify
it:

- **A bug** (wrong branch, missing side effect, wrong argument, off-by-one,
  missing validation of empty, zero, nil, unauthorised, or malformed input):
  write the test.
- **Only an intentional decision** (a constant's value, message wording,
  private structure, a call order nothing depends on): a change detector.
  Test the behaviour that depends on the decision instead; not
  `MAX_RETRIES == 5` but "the sixth attempt never happens".
- **Nothing**: redesign around an observable behaviour or drop the test.

## What to assert

Assert what a consumer observes: return value, persisted state, emitted
event, response body, exit code. Derive the expected value by hand, as a
literal or a hand-checked fixture, never by calling the code under test or
its helpers; a mirror assertion passes whatever the code does. Table-driven
tests with literal `want` columns are the preferred shape for several inputs
of one behaviour.

Test your contract at your boundary, not the framework's mechanics: the route
you register, the query you emit, the payload you produce. Asserting that the
router invokes a registered handler is the router's test. When a dependency's
behaviour genuinely surprised you, one narrow characterisation test naming
the assumption is enough.

One behaviour per test, named by behaviour, condition, and outcome
(`rejects expired token`, `returns empty list when no orders`), not by the
method it calls. Arrange, act, assert. Keep the arrange readable in place
(DAMP over DRY): helpers for setup are fine, helpers that hide the assertion
are not.

## Test doubles: real, then fake, then stub, then mock

Use the real thing until it is slow, external, or non-deterministic
(network, third-party service, clock, randomness, a large filesystem). Then
prefer, in order: a **fake** (working in-memory implementation of the same
interface), a **stub** (canned answer), a **mock** (asserts how it was
called). A mock is right only when the call itself is the contract: a
notification was sent once, with this payload.

Rules that hold at every level:

- Mock the level below the side effects the test depends on. Learn what the
  real method does first; a mock that swallows the config write the assertion
  reads passes for the wrong reason.
- A double mirrors the complete real structure, all documented fields, not
  just the ones this test reads; partial doubles pass while integration
  breaks.
- The mock earns no assertions about its own existence. If the assertion
  fails only when the mock is removed, unmock it or delete the assertion.
- Give each branch (success, error, malformed) its own fixture, so the wrong
  branch cannot satisfy the expectation.
- Cleanup only tests need lives in test utilities, never as a method on the
  production class.
- Mock setup longer than the test logic, or breaking whenever the mock
  changes: switch to an integration test with real components.

## Sizes and determinism

Push each test to the smallest size that can catch its break: unit for the
pure core (milliseconds, no I/O), integration for one real boundary (DB,
file, subprocess), end-to-end for a few critical paths only. Inject the clock
and randomness; no sleeps, no order dependence, no shared mutable fixtures;
every test passes alone and in the full suite. A flaky test is a bug to fix
or a test to delete, never one to retry.

## Characterisation tests before touching legacy code

For code with no tests and unclear intent, pin what it does today: capture
the current output for representative inputs as the expected value
(approval or golden style is fine), including behaviour you believe is
wrong, with a comment marking the quirk. Refactor under that net. Then fix
the quirk in its own commit and update the expectation to the intended
behaviour. Writing the "correct" expectation first turns a refactoring into
an undiagnosed behaviour change.

## Property-based tests where the contract is a property

Use a property test when the contract is stated over all inputs rather than
examples: round-trips (`decode(encode(x)) == x`), idempotency
(`f(f(x)) == f(x)`), invariants preserved under any operation, ordering
independence, "the parser never throws on arbitrary bytes". Use the
property-testing library already installed for the stack (fast-check,
Hypothesis, QuickCheck-style ports); do not add one for a single property.
Keep one concrete example beside the property so a failure reads plainly,
and let the library shrink the counterexample.

## Mutation check before finishing

Mentally mutate the production code and confirm at least one test fails for
each realistic mutation: wrong constant or argument, wrong branch handler,
missing state change or side effect, empty or default return, missing
validation of zero, empty, nil, unauthorised, or malformed input. A mutation
nothing catches marks the behaviour unprotected or the test tautological;
fix one or the other before yielding.

## Delete these on sight

Tests that pin wording, private structure, or source text; mirror
assertions; bare "does not throw" or "length grew"; tests that can fail only
through a crash; tests whose setup and assertion share the same object;
same-path parameter rows that exercise nothing new; tests written for
coverage that check no outcome. This applies regardless of who wrote them;
never re-pin such a test to the new text.

Sources: obra/superpowers `test-driven-development/writing-good-tests.md`
(name the break, mock gates, mutation check); addyosmani/agent-skills
`test-driven-development` (discover the stack first, real over fake over
stub over mock, DAMP); Feathers, *Working Effectively with Legacy Code*
(characterisation tests).
