# Trainer-owned contract

`TRAINER_OWNED_DATA_CONTRACT.md` documents the current Trainer-owned tables,
external references, stored values, and database programs. The authoritative
full structure is `../deployment/trainer-canonical-schema.sql`.

`PARTICIPANT_MATERIAL_ACCESS_CONTRACT.md` defines the read-only integration
contract EduTrain uses to evaluate participant access to Trainer-owned material.
Its course identity is direct:
`participant_material_access.course_id = course.courseID`, enforced by a
physical foreign key.

Historical standalone-schema exports and staged foreign-key scripts were
removed after canonical deployment verification because they were obsolete
and were not deployment inputs.
