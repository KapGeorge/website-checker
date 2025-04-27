# Website Availability and Title Checker (PHP)
## Project Description
This PHP script is designed for automated monitoring of websites' health and title verification.
It connects to each website from a predefined list, checks the server response, parses the <title> tag from the main page, and compares it against an expected value. If any discrepancies or errors are found, the script sends notifications directly to Telegram.

The script is intended to run automatically via a Cron job, enabling early detection of the following issues:

* Website is unavailable (errors like 404, 500, etc.)

* Title of the page has changed unexpectedly

* Server response errors

*  Immediate incident notification via Telegram

## How It Works
1. The script reads a list of websites and their expected titles.

2. It sends HTTP requests to each website.

3. It retrieves and verifies the HTTP status code.

4. If the response is successful (200 OK), it parses the HTML to extract the <title> tag.

5. It compares the actual title with the expected one.

6. If an error is detected:
    - It logs the issue.
    - Sends a notification to a Telegram chat or channel.

Everything runs automatically through a scheduled Cron task.

## Features
* Website availability monitoring

* Title tag verification

* Logging of successful and failed checks

* Real-time error notifications via Telegram

* Flexible configuration of website list and expected titles

* Easy integration with Cron for scheduled checks
