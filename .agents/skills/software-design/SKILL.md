---
name: software-design
description: >
  Load before shaping a module boundary, public interface, error-handling
  policy, or data model; before a refactoring larger than a rename; when a
  decision outlives a commit (ADR); and before writing a design doc. Design
  norms (deep modules, dependency direction, invariants, one error policy,
  when to abstract), the refactoring procedure, what a design doc must
  contain, and the ADR template and placement rule.
---

# software-design

Ponytail decides how much to build; this skill decides the shape of what gets
built. Review skills (`github-pr-review`, OMP `reviewer`) check the result
afterwards; apply this before and while writing. Harness and repository rules
take precedence over the norms below.

## Discover the repository's shape first

Read the neighbouring module, its tests, and any architecture note (README,
`docs/`, ADRs) before designing. Existing layering, error style, naming, and
directory layout override this skill; a second convention beside an existing
one is a defect, not a design choice. When the existing convention is the
problem, change it in its own commit with the reason in the body, or record
an ADR; never introduce the new style piecemeal.

## Design norms

Each norm ends with the question to ask of the code you are about to write.

**Deep modules.** An interface should be small relative to the functionality
it hides. A module whose interface is as large as its implementation
(pass-through methods, one-line wrappers, a "manager" forwarding to one
callee) adds a layer without hiding anything; inline it. *Does the caller
need to know less than before?*

**Dependency direction (the Clean Architecture rule).** Source dependencies
point inward only: entities and use cases know nothing of HTTP, DB,
filesystem, clock, UI, or framework; adapters at the edge depend on the
core, never the reverse. Where the core must call outward (a repository, a
gateway), the core owns the interface and the edge implements it. Crossing
a boundary passes simple data, not framework objects. Do not add the four
named layers to a small program: the rule is the direction, not the layer
count. *Can the core be exercised without network, disk, or a real clock,
and does any core file import an edge?*

**Validate at trust boundaries, trust inside.** User input, third-party
responses, environment, and files are untrusted; parse them into typed values
once at the edge and let internal code assume them valid. Re-validating
between internal functions hides which layer owns correctness. *Where does
this value cross a trust boundary, and is it parsed there exactly once?*

**Invariants in one place.** State what must always hold (non-empty cart,
unique key, ordered range) and enforce it where the value is created
(constructor, factory, DB constraint), not in every consumer. Prefer types
that make the illegal state unrepresentable: a sum type over a flag plus a
nullable, a parsed `Email` over a `string`. *Which single site guarantees
this invariant, and can a caller bypass it?*

**One error policy.** Follow the codebase's existing one; if there is none,
pick one and apply it everywhere. Expected outcomes (not found, invalid
input, conflict) are return values or a documented error type; programmer
errors and infrastructure failures fail fast. Define errors out of existence
when a total definition is safe (deleting a missing row is a no-op, an
out-of-range slice is empty). Add context where an error crosses a layer;
never swallow it, never catch-log-continue on the path that owns the data.
*Can the caller tell an expected outcome from a bug, and does any path drop
the error?*

**Abstract at the third concrete case, or at a boundary that must swap now.**
An interface with one implementation, a factory for one product, a config
knob for a value that never changes, a base class "for later": all
speculative generality, all deleted. Introduce the abstraction when three
call sites share the same shape, or when the seam is needed today for a
test double or a real second implementation. *What concrete second case
exists right now?*

**Observable behaviour is the contract (Hyrum's law).** Anything a consumer
can observe (ordering, timing, error text, undocumented fields) will be
depended on. Expose less; extend by addition (optional fields, new
functions) rather than modification; keep naming and list/filter shapes
consistent with the neighbours. *What am I exposing that I do not want to
support?*

**Cohesion by change axis.** Things that change together live together; a
module you describe with "and" is two modules. Shotgun surgery (one change
touching many files) and divergent change (one file changed for unrelated
reasons) are the two symptoms. *If requirement X changes, how many files
move?*

**Do one thing, compose (UNIX philosophy).** A program, command, or function
has one job and stops there; combine small ones instead of growing one.
Tools speak plain text or one structured format (JSON lines) on stdin and
stdout so they chain; diagnostics go to stderr; success is silent and
failure is a non-zero exit. Prefer a filter over a mode flag, and a pipeline
of existing tools over a new binary. *Could this be two things joined by a
pipe or a call, and does its output feed the next tool unchanged?*

**Facts, not derived state.** Persist and pass facts; compute views. Cached
or duplicated derived data needs an owner and an invalidation rule, so leave
it out until measured. Prefer a schema constraint to application code for
uniqueness, non-null, and referential integrity. *Which value is the source
of truth for this?*

**Time and concurrency are inputs.** Inject the clock and randomness. A
check-then-act sequence on shared state is a race; claim atomically (unique
constraint, compare-and-swap, `INSERT ... ON CONFLICT`). Any remote call has
three outcomes, success, failure, and unknown; record the intent before
calling out so a timeout after the effect applied can be resolved. *What
happens when two of these run at once, or the call times out after the
effect applied?*

## Gate before writing

Answer each in one line; a blank answer means the design is not ready.

1. Which existing module does this belong to, and why not there?
2. What does the caller need to know (interface), and what is hidden (depth)?
3. Where does untrusted data enter, and what type does it become?
4. What must always hold, and which site enforces it?
5. What does failure look like to the caller?

## Refactoring

Refactor when the current shape resists the change you need (make the change
easy, then make the easy change), or on green after a feature lands.
Behaviour change and refactoring never share a commit.

1. **Characterise first.** If the code you will move has no tests, write
   characterisation tests that pin the current behaviour, quirks included
   (see `test-design`). Do not fix the quirk yet.
2. **Find the seam.** A place where behaviour can be altered without editing
   the code in place: an injected dependency, a function boundary, a module
   export. Create one with a rename or an extracted parameter if none exists.
3. **One small behaviour-preserving step, then run the tests.** Rename,
   extract function, move function, inline, introduce parameter object,
   replace conditional with polymorphism: one per step. Use the language
   server for renames and `structural-edit` for repeated shape changes; hand
   edits miss call sites.
4. **Commit each green step** (`refactor:` subject, the reason in the body).
   Tests break in an unexpected way: revert the step, do not debug it.
5. **Fix behaviour afterwards** in a separate commit, updating the
   characterisation test to the intended behaviour.

Smell to move, for the smells that matter:

| Smell | Move |
| --- | --- |
| Long parameter list, same group passed around | Parameter object or value type |
| Function uses another object's data more than its own | Move function |
| Same shape in three places | Extract on the third copy, not the second |
| Primitive standing in for a concept (string id, int cents) | Value type carrying its invariant |
| Flag argument switching behaviour | Two functions |
| Nested conditionals | Guard clauses, early return |
| Comment explaining what the next lines do | Rename or extract until the comment is redundant |
| Interface, hook, or option nobody uses | Delete |

## Design docs

A design doc is the pre-implementation counterpart of an ADR: an ADR
records one decision, a design doc lays out a project so reviewers can find
the wrong decision before code exists. Write one when two or more hold:
several people implement it; more than about three months of work; it runs
in production for years; goals or requirements are ambiguous; a
catastrophic risk (security, data loss, legal) can be prevented at design
time. One "yes" is worth a one-pager; none means skip it.

Content filter: **what is the cost of being wrong?** A choice that is cheap
to reverse (a button placement, a page size) is not a design concern and
must not consume review cycles; a choice that is expensive to reverse
(language, storage, service boundary, data model, protocol) is the doc.

What each section must do:

- **Objective and background** read without any outside context: a
  stakeholder who has not talked to the author understands the problem and
  the motivation from the first page, with the numbers that prove it.
- **Goals** are stated as impact on users, the team, or the business, never
  as implementation ("fewer deploy-related outages", not "adopt
  Kubernetes"). **Non-goals** list what a reader would otherwise assume is
  in scope.
- **Scenarios** show the finished system as concrete step sequences when
  the goal alone does not make the behaviour obvious.
- **Interfaces** show the API, CLI, file format, or type signatures that
  consumers will see; UI as rough sketches only.
- **Dependencies** justify the ones that are hard to change later (language,
  storage, hosting); the easily swapped ones get a line.
- **SLOs** are measurable numbers ("p50 < 200 ms"), and **monitoring** says
  how a breach will be noticed.
- **Security** names the threats considered, the attack surface, and the
  trust boundaries (see "Validate at trust boundaries"); write the rationale
  even when the answer is "none apply" so reviewers can disagree.
- **Alternatives** cover only the strong candidates, a few lines each with
  the specific reason they lost.
- **Open issues** each state the problem, the options, a proposed answer,
  and the immediate next step. When resolved, move the entry to **Resolved
  issues** with the decision on top and the original discussion kept.

Diagrams come from an editable source kept next to the doc (`archify` on
this machine), never a photographed whiteboard. Use the repository's
existing design-doc location and headings; the HTML review workflow is
`reviewable-design-doc`, which handles rendering and comment threads, not
content.

## ADRs

Write an ADR for a decision that outlives a commit and is expensive to
reverse: a dependency or framework, a data model, a service or module
boundary, a protocol or API style, build or hosting. Local code choices go
in the commit body (Why) or a code comment (Why-not), not in an ADR.

**Placement: match the repository first.** Look for existing ADRs,
`.adr-dir`, `docs/adr/`, `docs/decisions/`, or ADR tooling, and continue
that directory, numbering, extension, and heading set. Only with no
convention use `docs/adr/NNNN-kebab-title.md`, starting at `0001`. Never
restart numbering or add a second scheme; if the evidence conflicts, ask.

Template (one page, in the repository's document language; Japanese ADRs go
through `natural-japanese`):

```markdown
# NNNN. <Decision stated as a sentence>

- Status: Proposed | Accepted | Superseded by NNNN
- Date: YYYY-MM-DD

## Context
The forces: requirement, constraint, measurement, deadline. Facts, not
opinions.

## Decision
What we will do, in one paragraph.

## Alternatives
One paragraph each: what it was and the specific reason it lost.

## Consequences
What becomes easier, what becomes harder, what we now have to do
(migration, monitoring, follow-up).
```

Never delete or rewrite an accepted ADR; supersede it with a new one that
links back. Link the ADR from the code or doc it governs when the decision
is not obvious from the code.

Sources: Ousterhout, *A Philosophy of Software Design*; Martin, *Clean
Architecture* (dependency rule); Raymond, *The Art of Unix Programming*
(do one thing, composition, silence on success); Fowler, *Refactoring*;
Feathers, *Working Effectively with Legacy Code*; Nygard's ADR format;
Lynch, "How to Write an Effective Software Design Document"
(refactoringenglish.com); addyosmani/agent-skills
`api-and-interface-design` and `documentation-and-adrs`.
