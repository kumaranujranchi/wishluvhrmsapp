# How to Set Up Cron Job (Auto-Emails)

To make the system automatically send Birthday wishes and Weekly Digests, you need to set up a **Cron Job** on your hosting server (Hostinger).

## Step 1: Find the File Path

First, you need to know where `cron_events.php` is located on your server.
Typically on Hostinger, it looks like this:
`/home/u743570205/domains/wishluvbuildcon.com/public_html/hrms/cron_events.php`

_Note: Verify the exact path in your File Manager._

## Step 2: Open Cron Jobs in Hostinger

1. Log in to your **Hostinger hPanel**.
2. Go to **Advanced** > **Cron Jobs**.
3. You will see a form to "Add New Cron Job".

## Step 3: Configure the Cron Job

Fill in the details as follows:

- **Type**: Custom
- **Common Settings**: Once Per Day (0 0 \* \* \*) or select "Once a day" from the dropdown.
  - _Recommended time is 09:00 AM so employees see it when they start work._
  - To run at 9 AM, set: `Minute: 0`, `Hour: 9`, `Day: *`, `Month: *`, `Weekday: *`.
- **Command**:
  ```bash
  /usr/bin/php /home/u743570205/domains/wishluvbuildcon.com/public_html/hrms/cron_events.php
  ```
  _(Replace the path with your actual path from Step 1 if it's different)_

## Step 4: Save

Click **Save** or **Add**.

## Type of Emails it will send:

1.  **Daily**: Happy Birthday / Anniversary emails to employees.
2.  **Weekly (Mondays)**: A digest of "Upcoming Celebrations" sent to all employees.
