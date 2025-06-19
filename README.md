## What is this?
This plugin allows a site manager to delete historical files stored within an assignment - at 1/2/3 year intervals. Note: it deletes the files but does not remove grades etc.

3 standard file areas are currently supported
* assignfeedback_editpdf
* assignfeedback_file
* assignsubmission_file

Purging files can be done in two ways:
- manual purge: schedules an adhoc task for the selected file area and interval.
- automated purge: can be enabled by file area, each with its own interval.

This was developed for a specific use-case and there is room for improvement - pull requests are always welcome.

## Example usage
![image](https://github.com/user-attachments/assets/bf20c6cc-3a9c-498a-9c88-a247c791159b)
