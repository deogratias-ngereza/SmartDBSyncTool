# Memory Bank Documentation

This directory contains Cline's persistent memory across sessions. All files follow specific conventions to maintain clarity and consistency.

## Core Files (Always Present)

1. **projectbrief.md** - Project overview, objectives, requirements
2. **productContext.md** - Why project exists, problems solved, workflows
3. **activeContext.md** - Current focus, recent changes, next steps
4. **systemPatterns.md** - Architecture, design patterns, component interactions
5. **techContext.md** - Technology stack, dependencies, development setup
6. **progress.md** - What works, what's left, current status

## Updates Directory

**Location**: `memory-bank/updates/`

**Purpose**: Store implementation status snapshots and major milestone documentation

**Naming Convention**: `YYYY-MM-DD_description.md`

**Examples**:
- `2026-03-23_phase1-2-completion.md` - Phase 1 & 2 implementation summary
- `2026-03-25_horizon-setup.md` - Horizon installation and configuration
- `2026-03-28_api-layer-complete.md` - API endpoints implementation

**What to Document Here**:
- ✅ Major feature completions
- ✅ Implementation milestones
- ✅ Architecture decisions with rationale
- ✅ Database schema changes
- ✅ API changes/additions
- ✅ Breaking changes
- ✅ Performance optimizations
- ✅ Security enhancements

**DO NOT Document Here**:
- ❌ Bug fixes (use git commits)
- ❌ Minor code refactoring
- ❌ Dependency updates (use CHANGELOG)
- ❌ Daily progress notes

## Documentation Rules

### For Implementation Updates
1. Create a new file in `memory-bank/updates/` for each major milestone
2. Use descriptive filename with date prefix: `YYYY-MM-DD_description.md`
3. Include completed features, file structure, test commands, and notes
4. Reference from `progress.md` when updating project status

### For Memory Bank Core Files
1. **Always read ALL core files** at the start of each session
2. Update `activeContext.md` frequently with current work
3. Update `progress.md` after completing phases/features
4. Update `systemPatterns.md` when adding new architectural patterns
5. Keep `techContext.md` current with new dependencies/tools

### Workspace Organization
- Root level: Keep README, LICENSE, composer/package files only
- Implementation details: Store in `memory-bank/updates/`
- Running documentation: Keep in core memory bank files
- Project-specific docs: Create in `docs/` if needed

## Quick Reference

### When Starting a Session
```bash
# Read these files first (in order):
1. projectbrief.md
2. activeContext.md
3. progress.md
4. Latest update in memory-bank/updates/
```

### When Ending a Session
```bash
# Update these if changes occurred:
1. activeContext.md (current focus)
2. progress.md (completed items)
3. Create new update file if milestone reached
```

### When Major Feature Complete
```bash
1. Create dated file in memory-bank/updates/
2. Update progress.md with completion status
3. Update activeContext.md with next steps
4. Consider updating systemPatterns.md if patterns changed
```

## Benefits of This Structure

✅ **Clean Root Directory** - No clutter from implementation docs  
✅ **Historical Record** - Track progress over time  
✅ **Easy Navigation** - Core files vs. milestone documentation  
✅ **Clear Context** - Always know what was done and when  
✅ **Session Continuity** - Cline can quickly resume work  

## Example Session Flow

```
Session Start:
→ Read projectbrief.md (understand goals)
→ Read activeContext.md (understand current state)
→ Read latest update file (understand recent work)
→ Continue implementation

Session End:
→ Update activeContext.md (document current state)
→ Update progress.md (mark completed items)
→ Create update file if milestone reached
```
