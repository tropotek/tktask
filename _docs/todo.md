
# TK Framework PHP 8.1 Rebuild 

- [ ] Update email for site settings to include from and reply-to (To avoid DKIM issues in the future)
- [ ] Implement the Status system
- [ ] Test \Bs\Ui\Dialog and see if it works as expected, maybe implement the form dialog too, (HTMX better??? test to see)
- [ ] Refactor and test the Mail system and templates
- [ ] Remove all project from bit-bucket to github, start using issue tracker and pull requests for projects
- [ ] Check if we need the HTMX installed for a base site, I suggest removing it and using it on a per-project basis
- [ ] Run `vendor/bin/phpstan analyse vendor/ttek/tk-base/Bs/` through all libs and remove all errors
- [ ] Create a DOM table renderer, may just need a new template (hopefully!!!!)


## Done

- [x] Finalise user login, account creation and permission system
- [x] Update the maintenance system
- [x] Implement global File object system
- [x] Check table csv (raw) values and html output and ensure there is a way to output raw data and html data to a cell
- [x] Implement a Form tabs jQuery plugin, that creates tab markup from a form group's template.
- [x] Implement PSR-4 namespaces
```json
{
  "autoload": {
    "psr-4": {
      "App\\": "src/App/"
    }
//    "psr-0": {
//      "App\\": "src/"
//    }
  }
}
```
