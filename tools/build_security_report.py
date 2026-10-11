from pathlib import Path
from xml.sax.saxutils import escape

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import (
    BaseDocTemplate,
    Frame,
    KeepTogether,
    PageBreak,
    PageTemplate,
    Paragraph,
    Spacer,
    Table,
    TableStyle,
)


ROOT = Path(__file__).resolve().parents[2]
OUTPUT = ROOT / "SPARK_Security_Measures_Report.pdf"

NAVY = colors.HexColor("#17324D")
BLUE = colors.HexColor("#2D6CDF")
MINT = colors.HexColor("#DDF5EA")
YELLOW = colors.HexColor("#FFF3C4")
RED = colors.HexColor("#FDE2E2")
LIGHT = colors.HexColor("#F4F7FA")
MID = colors.HexColor("#D7E0E8")
DARK = colors.HexColor("#243442")


class SecurityReport(BaseDocTemplate):
    def __init__(self, filename: str):
        super().__init__(
            filename,
            pagesize=A4,
            rightMargin=18 * mm,
            leftMargin=18 * mm,
            topMargin=20 * mm,
            bottomMargin=18 * mm,
            title="SPARK System Security Measures",
            author="SPARK Final Year Project",
            subject="Examiner preparation report for SPARK security controls",
        )
        frame = Frame(self.leftMargin, self.bottomMargin, self.width, self.height, id="normal")
        self.addPageTemplates(PageTemplate(id="report", frames=[frame], onPage=self._page))

    def _page(self, canvas, doc):
        canvas.saveState()
        if doc.page > 1:
            canvas.setStrokeColor(MID)
            canvas.line(18 * mm, A4[1] - 14 * mm, A4[0] - 18 * mm, A4[1] - 14 * mm)
            canvas.setFont("Helvetica", 8)
            canvas.setFillColor(DARK)
            canvas.drawString(18 * mm, A4[1] - 11 * mm, "SPARK System Security Measures")
            canvas.drawRightString(A4[0] - 18 * mm, 10 * mm, f"Page {doc.page}")
        canvas.restoreState()


styles = getSampleStyleSheet()
styles.add(ParagraphStyle(
    name="CoverTitle", parent=styles["Title"], fontName="Helvetica-Bold",
    fontSize=28, leading=34, textColor=NAVY, alignment=TA_CENTER, spaceAfter=10 * mm,
))
styles.add(ParagraphStyle(
    name="CoverSubtitle", parent=styles["Normal"], fontName="Helvetica",
    fontSize=13, leading=19, textColor=DARK, alignment=TA_CENTER,
))
styles.add(ParagraphStyle(
    name="H1Custom", parent=styles["Heading1"], fontName="Helvetica-Bold",
    fontSize=18, leading=22, textColor=NAVY, spaceBefore=4 * mm, spaceAfter=3 * mm,
))
styles.add(ParagraphStyle(
    name="H2Custom", parent=styles["Heading2"], fontName="Helvetica-Bold",
    fontSize=13, leading=17, textColor=BLUE, spaceBefore=3 * mm, spaceAfter=2 * mm,
))
styles.add(ParagraphStyle(
    name="BodyCustom", parent=styles["BodyText"], fontName="Helvetica",
    fontSize=9.4, leading=14, textColor=DARK, spaceAfter=2.5 * mm,
))
styles.add(ParagraphStyle(
    name="SmallCustom", parent=styles["BodyText"], fontName="Helvetica",
    fontSize=8.2, leading=11, textColor=DARK,
))
styles.add(ParagraphStyle(
    name="SmallHeader", parent=styles["BodyText"], fontName="Helvetica-Bold",
    fontSize=8.2, leading=11, textColor=colors.white,
))
styles.add(ParagraphStyle(
    name="BulletCustom", parent=styles["BodyText"], fontName="Helvetica",
    fontSize=9.2, leading=13, leftIndent=5 * mm, firstLineIndent=-3 * mm,
    bulletIndent=1 * mm, textColor=DARK, spaceAfter=1.6 * mm,
))
styles.add(ParagraphStyle(
    name="Question", parent=styles["BodyText"], fontName="Helvetica-Bold",
    fontSize=9.5, leading=13, textColor=NAVY, spaceAfter=1.5 * mm,
))


def p(text, style="BodyCustom"):
    return Paragraph(text, styles[style])


def bullet(text):
    return Paragraph(f"• {escape(text)}", styles["BulletCustom"])


def table(data, widths, header=True):
    converted = []
    for row_index, row in enumerate(data):
        style = "SmallHeader" if header and row_index == 0 else "SmallCustom"
        converted.append([Paragraph(escape(str(cell)), styles[style]) for cell in row])
    result = Table(converted, colWidths=widths, repeatRows=1 if header else 0, hAlign="LEFT")
    commands = [
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("GRID", (0, 0), (-1, -1), 0.45, MID),
        ("LEFTPADDING", (0, 0), (-1, -1), 6),
        ("RIGHTPADDING", (0, 0), (-1, -1), 6),
        ("TOPPADDING", (0, 0), (-1, -1), 5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, LIGHT]),
    ]
    if header:
        commands.extend([
            ("BACKGROUND", (0, 0), (-1, 0), NAVY),
            ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
        ])
    result.setStyle(TableStyle(commands))
    return result


def callout(title, text, background=MINT):
    content = Paragraph(f"<b>{escape(title)}</b><br/>{escape(text)}", styles["BodyCustom"])
    box = Table([[content]], colWidths=[168 * mm])
    box.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), background),
        ("BOX", (0, 0), (-1, -1), 0.8, NAVY),
        ("LEFTPADDING", (0, 0), (-1, -1), 10),
        ("RIGHTPADDING", (0, 0), (-1, -1), 10),
        ("TOPPADDING", (0, 0), (-1, -1), 8),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 8),
    ]))
    return box


story = [
    Spacer(1, 28 * mm),
    p("SPARK", "CoverTitle"),
    p("System Security Measures", "CoverTitle"),
    Spacer(1, 4 * mm),
    p("A simple examiner-preparation guide for the gamified web-based Science learning system", "CoverSubtitle"),
    Spacer(1, 18 * mm),
    callout(
        "Purpose of this report",
        "This document explains the security controls that are currently implemented in SPARK, why each control is needed, how it is tested, and which improvements remain future work.",
        LIGHT,
    ),
    Spacer(1, 22 * mm),
    p("Prepared for Final Year Project examination", "CoverSubtitle"),
    p("Status: implemented and verified in the local development environment", "CoverSubtitle"),
    p("Date: 5 October 2026", "CoverSubtitle"),
    PageBreak(),
    p("1. Security Overview", "H1Custom"),
    p("SPARK protects three main assets: user accounts, student learning records, and learning content. Its controls follow three simple goals:"),
    bullet("Confidentiality: only an authorised person can view information intended for that role or assigned class."),
    bullet("Integrity: quiz scores, answers, account status and academic-year records cannot be changed through an unauthorised route."),
    bullet("Availability: failed AI requests do not stop a quiz; the system falls back to teacher explanations and a fixed message."),
    callout(
        "One-sentence examiner answer",
        "SPARK uses server-side authentication, role-based access control, validation, database constraints, rate limiting, secure file handling and controlled Gemini integration to protect student records and prevent misuse.",
    ),
    p("1.1 Role and permission model", "H2Custom"),
    table([
        ["Role", "Permitted functions", "Restricted functions"],
        ["Student", "Own dashboard, published modules, own quizzes, own progress", "Cannot access teacher or administrator routes"],
        ["Teacher", "Learning content, quiz questions, reports for assigned classes", "Cannot manage users, classes, academic years or rollover"],
        ["Administrator", "User accounts, classes, academic years, rollover, all reports, learning content", "Cannot reset another administrator's password; cannot deactivate own account"],
    ], [28 * mm, 70 * mm, 70 * mm]),
    p("The public login still shows Pelajar and Guru. The Guru selection accepts both teacher and administrator accounts, but the server reads the stored role and sends each account only to routes permitted for that role."),
    PageBreak(),
    p("2. Implemented Security Measures", "H1Custom"),
    p("2.1 Authentication and password protection", "H2Custom"),
    bullet("Passwords are stored with Laravel's one-way password hashing. Plain-text passwords are not stored."),
    bullet("Login requires the correct username, password, selected interface role and an Active account status."),
    bullet("Student login additionally requires an active academic year."),
    bullet("Login is limited to five attempts per minute for each username and IP address combination."),
    bullet("Sensitive administrator pages require a recently confirmed password before access."),
    bullet("Administrator-assisted password resets revoke the affected student's or teacher's existing sessions."),
    p("2.2 Authorisation and least privilege", "H2Custom"),
    bullet("Route middleware separates student, teacher and administrator areas."),
    bullet("Form Request authorisation checks repeat the role check on sensitive submissions."),
    bullet("Teachers see reports only for classes assigned through the database relationship."),
    bullet("Administrators are created only through the local console command spark:create-admin, not through the web form."),
    bullet("A teacher can be deactivated by an administrator. The current administrator cannot deactivate their own account, and the last active administrator is protected."),
    p("2.3 Account and academic-year lifecycle", "H2Custom"),
    bullet("Public student registration creates a Pending account rather than immediate access."),
    bullet("An administrator approves or rejects registrations."),
    bullet("Inactive accounts are logged out by middleware and cannot continue using an old session."),
    bullet("Academic-year rollover uses a database transaction and keeps previous student results as historical records."),
    p("2.4 Input, session and browser protection", "H2Custom"),
    bullet("Laravel CSRF tokens protect state-changing web forms."),
    bullet("Server-side validation checks usernames, status values, class IDs, file types, file size and quiz data."),
    bullet("Blade output uses escaped rendering for user-supplied text, reducing cross-site scripting risk."),
    bullet("Registration is limited to five requests per hour per IP address."),
    bullet("Recovery requests are limited by both IP address and username, while the response never confirms whether the username exists."),
    PageBreak(),
    p("3. Quiz and Learning Content Security", "H1Custom"),
    p("3.1 Quiz integrity", "H2Custom"),
    bullet("The correct option is hidden from the student question page and checked only on the server."),
    bullet("A saved answer is locked and cannot be changed after submission."),
    bullet("A unique database rule prevents duplicate answers for the same attempt and question."),
    bullet("Question, selected-answer, correct-answer and explanation snapshots preserve historical accuracy after a teacher edits a question."),
    bullet("Quiz-answer submissions are limited to 30 requests per minute per signed-in student."),
    p("3.2 File and document controls", "H2Custom"),
    bullet("Quiz images are limited to approved image types and 5 MB per file."),
    bullet("Learning documents are handled as PDF files and served from private storage through an authenticated controller."),
    bullet("PDF paths are checked for the expected directory and path traversal sequences."),
    bullet("PDF responses use private, no-store caching, nosniff and same-origin frame headers."),
    bullet("YouTube URLs are validated and embedded through youtube-nocookie.com rather than accepting uploaded video files."),
    p("3.3 Database integrity", "H2Custom"),
    bullet("Foreign keys prevent orphaned student, class, quiz and academic-year records."),
    bullet("Database transactions and row locks protect multi-step operations such as scoring, account status changes and rollover."),
    bullet("Unique constraints protect usernames, teacher-class assignments and one answer per question in an attempt."),
    bullet("Status-based deactivation is preferred over deleting historical learning records."),
    PageBreak(),
    p("4. Gemini AI Feedback Security", "H1Custom"),
    callout(
        "Current usage scope",
        "Gemini feedback is currently intended only for prototype testing by the student developer and supervisor. It should remain disabled for real Year 6 users until terms, school approval, privacy requirements and safeguarding have been reviewed.",
        YELLOW,
    ),
    p("4.1 Controls implemented", "H2Custom"),
    bullet("The API feature is disabled by default with QUIZ_AI_FEEDBACK_ENABLED=false."),
    bullet("The API key is read from environment configuration and is never sent to the browser."),
    bullet("Only quiz context is sent. Student name, username, class and score are not included."),
    bullet("The system prompt tells Gemini to treat quiz data as reference data, not instructions, which reduces prompt-injection risk."),
    bullet("Requests use fixed Google endpoints, a validated model name, short timeouts and no HTTP redirects."),
    bullet("Only plain text of a limited length is accepted. Provider response bodies and keys are not written to logs."),
    bullet("If Gemini fails, is disabled or is rate-limited, the student sees a fixed Bahasa Melayu fallback and can continue the quiz."),
    p("4.2 Cost and abuse controls", "H2Custom"),
    bullet("Only wrong answers are eligible for AI feedback."),
    bullet("Generated feedback is cached by question content, answer options, selected option, correct option, explanation, curriculum context and model."),
    bullet("The same valid explanation can therefore be reused without another provider request."),
    bullet("Each student is limited to 10 uncached requests per hour by default."),
    bullet("The entire system is limited to 100 uncached requests per day by default."),
    bullet("Administrators can see daily Gemini calls and cache hits on the dashboard."),
    p("4.3 What is not sent to Gemini", "H2Custom"),
    table([
        ["Not sent", "Reason"],
        ["Student name or username", "Not required to explain a Science answer"],
        ["Class and academic year", "Avoids unnecessary personal or school context"],
        ["Password or API key", "Secrets must remain on the server"],
        ["Quiz images", "The current integration is text-only; image-dependent questions need a teacher explanation"],
    ], [65 * mm, 103 * mm]),
    PageBreak(),
    p("5. Threat-to-Control Summary", "H1Custom"),
    table([
        ["Threat", "Control used in SPARK", "Result"],
        ["Brute-force login", "Login rate limit", "Repeated guesses are slowed"],
        ["Fake registrations", "Registration rate limit and Pending status", "No immediate student access"],
        ["Teacher reaches admin tools", "Separate roles plus route and request checks", "Request is denied"],
        ["Teacher views another class", "Teacher-class assignment query scope", "Unassigned records are excluded"],
        ["Stolen active session", "Account-status middleware and session revocation", "Deactivated account is logged out"],
        ["Changed quiz answer", "Server-side checking and one saved response", "Submitted answer is locked"],
        ["API quota exhaustion", "Cache, per-student limit, daily limit and dashboard counter", "Provider calls are reduced and visible"],
        ["Gemini unavailable", "Timeouts and fixed fallback message", "Quiz remains usable"],
        ["Malicious upload", "MIME, extension and size validation", "Unsupported files are rejected"],
        ["Orphaned records", "Foreign keys and restrictive deletion", "Historical data remains linked"],
    ], [42 * mm, 78 * mm, 48 * mm]),
    p("6. Verification Evidence", "H1Custom"),
    bullet("Automated tests: 272 total, 262 passed and 10 intentionally skipped."),
    bullet("Static analysis: PHPStan passed with zero errors."),
    bullet("Code style: Laravel Pint passed."),
    bullet("Frontend compilation: npm run build passed."),
    bullet("Dependency checks: composer audit and npm audit reported no known vulnerabilities."),
    bullet("MySQL migrations applied successfully to the local SPARK database."),
    p("Important security test cases include role separation, class scoping, recent password confirmation, account deactivation, recovery throttling, AI caching, AI request limits and server-side quiz feedback."),
    PageBreak(),
    p("7. Known Limitations and Future Improvements", "H1Custom"),
    table([
        ["Current limitation", "Recommended next improvement"],
        ["Two-factor authentication is not enabled", "Add TOTP-based 2FA and recovery-code procedures for administrators"],
        ["The system runs locally and is not deployed", "If deployed, enforce HTTPS, secure cookies, production error settings and a firewall"],
        ["Audit history is strongest for rollover but not every admin action", "Add an append-only security audit log for account, class and content changes"],
        ["Backup recovery is not yet demonstrated", "Create encrypted database backups and test restoration"],
        ["Gemini is prototype-only", "Complete policy, consent, age-suitability and data-protection review before real student use"],
        ["No formal penetration test has been completed", "Run OWASP-focused testing before production deployment"],
    ], [68 * mm, 100 * mm]),
    callout(
        "Do not overclaim",
        "During the examination, clearly separate controls already implemented from future recommendations. For example: password confirmation is implemented; 2FA is recommended but not yet implemented.",
        RED,
    ),
    p("8. Examiner Questions and Short Answers", "H1Custom"),
]

qa = [
    ("How do you stop a student from accessing the teacher dashboard?", "Protected routes use authentication and role middleware. A student account does not have the teacher or administrator role, so the server returns a forbidden response even if the URL is typed manually."),
    ("Why did you separate Teacher and Administrator?", "Least privilege. A teacher needs content, quiz and assigned-class reporting functions, but does not need account creation, class deletion or academic-year rollover permissions."),
    ("Can a teacher view every student?", "No. Teacher report queries are restricted to classes assigned to that teacher. Administrators can view all classes."),
    ("How are passwords protected?", "Laravel hashes passwords before storage. Login compares the password against the hash, and sensitive administrator pages require recent password confirmation."),
    ("How do you protect the Gemini API key?", "The key is stored in server environment configuration and used only by the backend. It is never placed in Blade HTML, JavaScript or the student browser."),
    ("What happens when Gemini fails?", "SPARK records no generated text, shows a fixed safe fallback message and allows the student to continue. The quiz does not depend on the API being available."),
    ("How do you avoid wasting the free Gemini quota?", "SPARK caches feedback for the same question and selected answer, limits each student per hour, limits the system per day and shows usage to the administrator."),
    ("Is the correct answer visible before submission?", "No. It is hidden from the question output and checked on the server after the student submits an option."),
    ("Is the system completely secure?", "No system is completely secure. SPARK applies controls appropriate to the current local prototype, while 2FA, deployment hardening, broader audit logs, backup recovery and penetration testing remain future work."),
]

for question, answer in qa:
    story.append(KeepTogether([
        p("Q: " + escape(question), "Question"),
        p("A: " + escape(answer)),
        Spacer(1, 2 * mm),
    ]))

story.extend([
    PageBreak(),
    p("9. Demonstration Checklist", "H1Custom"),
    p("Use this short sequence if an examiner asks for a security demonstration:"),
    bullet("Log in as a teacher and attempt to open /guru/pengguna. Show that access is denied."),
    bullet("Open Pantau Kemajuan as a teacher and show that only assigned classes appear."),
    bullet("Log in as an administrator and open Urus Pengguna. Show the password-confirmation step."),
    bullet("Deactivate a test teacher and show that the account can no longer continue using its old session."),
    bullet("Answer a quiz question incorrectly and show the server-generated feedback or safe fallback."),
    bullet("Repeat an equivalent wrong answer and explain that cached feedback avoids a second API call."),
    bullet("Open the administrator dashboard and show the daily Gemini API and cache counters."),
    p("10. Main Technical Evidence", "H1Custom"),
    table([
        ["Control", "Main implementation location"],
        ["Role separation and routes", "app/Enums/UserRole.php; routes/web.php"],
        ["Class assignment", "app/Models/User.php; kelas_teacher migration"],
        ["Password confirmation", "routes/web.php; Fortify confirmation view"],
        ["Account status protection", "SetManagedAccountStatus.php; EnsureAccountIsActive.php"],
        ["Public throttles", "FortifyServiceProvider.php"],
        ["AI cache and limits", "GenerateAiFeedback.php; config/quiz.php"],
        ["Gemini request safety", "AiFeedbackService.php"],
        ["Daily AI counter", "ProgressReportController.php; teacher dashboard"],
        ["Security tests", "tests/Feature/SecurityControlsTest.php; GeminiFeedbackTest.php"],
    ], [58 * mm, 110 * mm]),
    Spacer(1, 5 * mm),
    callout(
        "Final examiner summary",
        "SPARK applies defence in depth: a request must pass authentication, account status, role permission, input validation and database rules. External AI is optional, restricted and designed to fail safely.",
    ),
])


doc = SecurityReport(str(OUTPUT))
doc.build(story)
print(OUTPUT)
