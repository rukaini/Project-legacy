# Source map

Portable CSS uses renamed `.ui-*` selectors. Unless noted, visual values are selector-adapted from exact source blocks. Snapshot paths below are relative to `source-exact/`.

| Component | Classification | Canonical / variant source | Snapshot(s) | Portable CSS |
|---|---|---|---|---|
| Header | Global canonical | `resources/views/components/app-signed-in-header-styles.blade.php`; Admin/Trainer navbar partials | matching `resources/views/...` paths | Selector-adapted, exact visual values |
| Page shell | Global canonical | `resources/views/components/app-page-shell-styles.blade.php` | same path | Selector-adapted, exact values |
| Page heading | Canonical variant | `resources/views/trainer/training/my-training.blade.php`; profile styles | matching snapshots | Composed from exact source blocks |
| Breadcrumbs | Workspace canonical | `resources/views/trainer/training/partials/workspace-header-styles.blade.php` and markup | matching snapshots | Selector-adapted |
| Cards | Canonical variants | Trainer/Admin dashboards; My Training; workspace/Attendance panels | dashboard, My Training, Attendance snapshots | Composed; variants remain explicit |
| Buttons | Canonical variants | Trainer dashboard/My Training/Attendance; Admin system styles | matching snapshots | Composed; density variants remain explicit |
| Forms | Canonical variants | Trainer profile; My Training; Admin system styles | matching snapshots | Composed; compact/profile distinctions documented |
| Search/filters | Canonical variants | My Training; Admin Training Monitoring | My Training and Admin courses snapshots | Composed from exact blocks |
| Badges/status | Canonical variants | My Training; workspace header; Admin courses; Attendance | matching snapshots | Composed semantic variants |
| Tabs | Canonical variants | Workspace header; Admin Training Monitoring | workspace/Admin courses snapshots | Workspace block selector-adapted; Admin remains documented variant |
| Tables | Canonical variants | Trainer dashboard; Attendance; Admin system styles | matching snapshots | Dashboard and dense Attendance variants composed |
| Pagination | Product-specific canonical | `resources/views/trainer/training/my-training.blade.php` | same snapshot | Selector-adapted; fixed-five behavior retained |
| Dropdown actions | Page-specific / not globally standardized | Navbar and workflow-local action controls | navbar and page snapshots | Structural portable adaptation; sizing remains context-specific |
| Modal | Canonical variants | Attendance styles/scripts; submission styles/markup; QR styles | matching snapshots | Attendance/submission values composed; no fake global width |
| Toast | Global canonical | Three shared toast components | three toast snapshots | CSS selector-adapted; JS API adapted without Laravel session syntax |
| Alerts/messages | Canonical variants | Admin courses feedback; Attendance report error; profile field error | matching snapshots | Exact semantic variants composed |
| States | Canonical variants | Admin system empty state; profile empty state; Attendance/My Training logic | matching snapshots | Exact empty/loading/error values composed |
| Workspace navigation | Workspace canonical | Workspace header styles/markup; Attendance variant | matching snapshots | Selector-adapted |
| Master/detail | Canonical variants | Admin Training Monitoring; Trainer Attendance | Admin courses and Attendance snapshots | Exact widths/breakpoints composed |
| Collapse navigator | Canonical variants | Admin courses styles/scripts; Attendance styles/scripts | matching snapshots | Selector-adapted; exact motion values and inert behavior |

Foundation/dependency evidence also includes Admin/Trainer layouts, `package.json`, `package-lock.json`, `vite.config.js`, and `composer.json`. Terminology maps to `app/Support/ParticipantTypeLabel.php`.

## Inventoried page families

Admin: Dashboard, Proposals, Activity, Trainings/Monitoring, Overview, Teaching Plans, Sessions, Participants, Attendance, Directory, Analytics, and Profile.

Trainer: Dashboard, My Training, Proposal, Analytics, Feedback, Profile, LMS and Simple workspaces, Overview, Teaching Plans, Sessions, Participants, Attendance, Learner Tracking, materials/content, assessments, submissions/review, certificates, and QR display.

The inventory was used to find unique presentation families, not to turn pages into components. Analytics charts, assessment/material authoring, submission review, and QR display remain page-specific. The initials brand, subsystem subtitles, fabricated Trainer hierarchy, QR fullscreen, direct external QR action, fake universal KPI, and fake global modal width are legacy/wrong patterns and are not standards.
