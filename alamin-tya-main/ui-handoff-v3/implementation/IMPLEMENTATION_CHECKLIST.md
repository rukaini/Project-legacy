# Implementation checklist

## Branding and foundation

- [ ] Official logo copied unchanged; no initials or duplicate wordmark/subtitle
- [ ] 58px header and 1880px shell retained
- [ ] Canonical header/page gutters align
- [ ] Inter delivery decision documented

## Components

- [ ] Correct card/button/form/table/modal variant selected
- [ ] Search and filters reset pagination to page 1
- [ ] Fixed-five pagination summary is truthful; one-page controls hidden
- [ ] Tabs/tables scroll locally where necessary
- [ ] Empty/loading/error/disabled states are truthful
- [ ] Toast and collapse JS included only when required

## Interaction and accessibility

- [ ] Hover, focus, pending, and disabled states work
- [ ] Keyboard and visible focus verified
- [ ] Collapses synchronize `aria-expanded`, `aria-hidden`, and `inert`
- [ ] Modal Escape/close/focus return works
- [ ] Icon-only controls have accessible names
- [ ] Reduced motion verified

## Responsive

- [ ] Desktop and centered wide view
- [ ] 50/67/80/90/100/110/125/150/175/200% zoom review
- [ ] 768/820/991/1024px tablet review
- [ ] 320/360/375/390/412/480px mobile review
- [ ] No accidental page-level horizontal overflow

## Terminology

- [ ] `outsider` displays as Public
- [ ] `guru_new`, Guru Baru, and Guru New display as New Teacher
- [ ] Internal values remain unchanged
- [ ] No fabricated Trainer hierarchy
- [ ] Application UI is English; stored business content is not auto-translated
