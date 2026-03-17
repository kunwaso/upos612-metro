# Research and Delegation

Use this document for deep investigations, long external-repo intake, or any task where one search path is not enough.

The goal is to stay parallel, bounded, and evidence-first without turning the task into open-ended exploration.

---

## 1. When to Read This

Read this for:

- external repo evaluation
- multi-step architecture comparisons
- long bug investigations with multiple plausible causes
- module audits that touch many files
- tasks where one branch needs upstream facts and another needs local-fit analysis

For simpler work, stay with the normal `tiny`, `investigate`, or `implement` lanes.

---

## 2. Default Research Split

When a task is large enough to benefit from parallel reasoning, split it into three bounded branches:

| Branch | Goal | Output |
|---|---|---|
| `upstream facts` | Gather README, manifests, license, releases, and example flows | Facts only, no local recommendation yet |
| `local fit` | Compare the repo's conventions, modules, UI, security, and landing paths | Landing constraints and risks |
| `synthesis` | Merge the first two branches into one recommendation | Adopt/adapt/reject plus landing path and verification |

Do not let one branch invent conclusions that belong to another branch.

---

## 3. Startup Order

Before you branch out:

1. Read `project_map` or filesystem so checkout truth is current.
2. Read `resource://composer` when dependencies may change.
3. Read the nearest `ai/*.md` domain docs.
4. Only then split into upstream and local-fit work.

This avoids spending deep research time on a landing path the repo does not even support.

---

## 4. Bounded Task Rules

- Each branch must have a single question to answer.
- Each branch should return evidence, not prose padding.
- Each branch should know whether it is allowed to edit or only inspect.
- Each branch should end with a short, mergeable output.

Good branch outputs:

- “The repo depends on X, requires Y license, and assumes browser-side action approval.”
- “The local landing point would be `Modules/Aichat`, not root, because tenant scope and UI assets already exist there.”

Bad branch outputs:

- “Here is a big brainstorm with no decision.”
- “I copied the upstream file structure and we can figure it out later.”

---

## 5. When Not to Delegate

Do **not** split the task if:

- the next local step is blocked on one direct read
- the task is a tiny one-file change
- the exploration cost is larger than the likely insight
- the branch outputs would overlap heavily and create duplicate work

Bounded parallelism helps when the branches are clearly different, not when they all answer the same question.

---

## 6. Required Synthesis Output

The final synthesis should always include:

1. Decision or diagnosis
2. Evidence from each branch
3. Landing path or fix path
4. Verification plan
5. Remaining uncertainty, if any

For external intake, the synthesis must still end with `adopt`, `adapt`, or `reject`.

---

## 7. Verification

Before calling the investigation done:

- confirm the branches did not contradict each other
- confirm the final recommendation mentions repo-specific constraints
- confirm tool degradation did not silently reduce confidence
- confirm the output is shorter and clearer than the combined branch notes
