# Employee Import Validation Rules

When processing or building the code for the Employee Excel / CSV upload feature:

1. **Header Normalization (Case & Space Insensitive):**
   - Accept any variation of column headers (e.g., "Employee Name", "EMPLOYEE_NAME", "employee name", "Title", "Job Title", "Project", "Date").
   - Strip extra spaces, ignore capital/lowercase letters, and map them to standard internal keys (`employee_name`, `job_title`, `project`, `material_type`, `date`).

2. **Flexible Date Formatting:**
   - Do NOT reject dates that are not in `YYYY-MM-DD` format.
   - Accept standard date formats including `MM/DD/YYYY`, `DD/MM/YYYY`, `YYYY/MM/DD`, or Excel serial date integers, and convert them to `YYYY-MM-DD` before storing.

3. **User Feedback:**
   - Always return clear error messages or success logs if an upload succeeds or fails so the page never freezes silently.
