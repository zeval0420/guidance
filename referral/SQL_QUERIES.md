# SQL to run manually (phpMyAdmin, database `dbadmin`)

The backup dump of `guidance_referral_form` lacks the review columns the dashboard
uses and has no follow-up date for the calendar. Run once:

```sql
ALTER TABLE `guidance_referral_form`
  ADD COLUMN `followup_date` DATE NULL DEFAULT NULL AFTER `requires_followup`,
  ADD COLUMN `status` ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  ADD COLUMN `reviewed_by` VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN `reviewed_at` DATETIME NULL DEFAULT NULL,
  ADD INDEX `idx_referral_date` (`date_referred`),
  ADD INDEX `idx_referral_followup` (`followup_date`);
```

Skip any column you already have (check with `SHOW COLUMNS FROM guidance_referral_form;`).
If `status` already exists with different values (e.g. `Reviewed`, `In Progress`), use:

```sql
ALTER TABLE `guidance_referral_form`
  MODIFY `status` ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending';
```
