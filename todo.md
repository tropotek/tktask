# TODO list

- [ ] Finalise user login, account creation and permission system
- [ ] Update the maintenance system
- [ ] Update email for site settings to include from and reply-to (To avoid DKIM issues in the future)
- [ ] Implement global filesystem
- [ ] Implement the status system
- [ ] Update and finalise the Dialogs and form dialog UI
- [ ] Refactor and test the Mail system and templates
- [ ] Remove all project from bit-bucket to github, start using issue tracker and pull requests for projects
- [ ] Check if we need the HTMX installed for a base site, I suggest removing it and using it on a per-project basis
- [ ] Audit all Events and minimise on their usage in future projects. Useful for lower level libs or extensions.
most of the time adding callbacks to an object would suffice where needed.
- [ ] Check table csv (raw) values and html output and ensure there is a way to output raw data and html data to a cell
- [ ] Implement a Form tabs jQuery plugin, that creates tab markup from a form group's template.
- [ ] Run `vendor/bin/phpstan analyse vendor/ttek/tk-base/Bs/` through all libs and remove all errors






