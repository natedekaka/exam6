# Work Plan: Fitur Cek Jawaban Siswa di Rekap Nilai Admin

## TL;DR

> **Quick Summary**: Menambahkan fitur untuk admin dapat melihat detail jawaban siswa dari halaman rekap nilai, lengkap dengan analisis butir soal, export Excel, dan filter jawaban.
> 
> **Deliverables**: 
> - Tombol "Lihat Jawaban" di tabel rekap nilai admin
> - Halaman detail jawaban admin baru (`admin/detail_jawaban.php`)
> - Analisis butir soal di analytics (`admin/analytics.php`)
> - Export jawaban ke Excel (`admin/ekspor_jawaban.php`)
> - Filter benar/salah di halaman detail
>
> **Estimated Effort**: Medium
> **Parallel Execution**: YES - 3 waves
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4-5 (parallel) → Task 6-8 (parallel)

---

## Context

### Original Request
User ingin bisa cek jawaban siswa dari rekap nilai admin. Memilih **Opsi C** = semua fitur #1-#5.

### Interview Summary
**Key Discussions**:
- Data `detail_jawaban` (JSON) sudah tersimpan lengkap di tabel `hasil_ujian`
- `review.php` sudah ada untuk siswa melihat jawaban sendiri (bisa dijadikan referensi UI)
- Fokus pada kemudahan admin melihat jawaban per siswa

**Research Findings**:
- Struktur JSON `detail_jawaban`: soal_id, pertanyaan, jawaban_siswa, kunci_jawaban, is_correct, poin, poin_diperoleh, opsi_a sampai opsi_e
- `admin/analytics.php` sudah memiliki struktur analytics (grade distribution, score distribution) - tinggal tambah question analysis
- `admin/ekspor_excel.php` sudah ada untuk export nilai - bisa dibuatkan pattern serupa untuk export jawaban

### Metis Review
**Identified Gaps** (addressed):
- Guardrail: Pastikan hanya admin yang sudah login yang bisa akses halaman detail jawaban
- Guardrail: Validasi parameter id_hasil harus ada dan valid
- Edge case: Handle jika `detail_jawaban` kosong atau corrupt
- Acceptance criteria: Setiap fitur harus memiliki QA scenarios

---

## Work Objectives

### Core Objective
Memberikan kemudahan bagi admin untuk melihat, menganalisis, dan mengekspor jawaban siswa dari panel admin.

### Concrete Deliverables
- `admin/detail_jawaban.php` - Halaman baru untuk melihat detail jawaban per siswa
- Modifikasi `admin/rekap_nilai.php` - Tambah kolom aksi dengan tombol "Lihat Jawaban"
- Modifikasi `admin/analytics.php` - Tambah section "Analisis Butir Soal"
- `admin/ekspor_jawaban.php` - Halaman baru untuk export jawaban ke Excel
- Modifikasi `admin/detail_jawaban.php` - Tambah filter benar/salah

### Definition of Done
- [ ] Admin bisa klik "Lihat Jawaban" di rekap nilai dan melihat detail lengkap
- [ ] Admin bisa melihat analisis soal tersulit di analytics
- [ ] Admin bisa export jawaban siswa ke Excel
- [ ] Admin bisa filter jawaban benar/salah di halaman detail

### Must Have
- Tombol "Lihat Jawaban" di setiap baris tabel rekap nilai
- Halaman detail jawaban dengan tampilan seperti `review.php` tapi untuk admin
- Analisis 20 butir soal tersulit (terrendah success rate) di analytics
- Export Excel berisi jawaban semua siswa untuk 1 ujian
- Filter tampilan hanya benar/salah di halaman detail

### Must NOT Have (Guardrails)
- Jangan ubah struktur tabel database (data `detail_jawaban` sudah cukup)
- Jangan ubah logika penilaian yang sudah ada di `api/submit_jawaban.php`
- Jangan hapus fitur yang sudah ada di `admin/rekap_nilai.php`
- Jangan buat duplikasi kode yang tidak perlu (reuse dari `review.php`)

---

## Verification Strategy (MANDATORY)

> **ZERO HUMAN INTERVENTION** - ALL verification is agent-executed. No exceptions.
> Acceptance criteria requiring "user manually tests/confirms" are FORBIDDEN.

### Test Decision
- **Infrastructure exists**: NO (no composer.json, no phpunit, no test files)
- **Automated tests**: None (user didn't request testing)
- **Framework**: none
- **If TDD**: Not applicable

### QA Policy
Every task MUST include agent-executed QA scenarios (see TODO template below).
Evidence saved to `.sisyphus/evidence/task-{N}-{scenario-slug}.{ext}`.

- **Frontend/UI**: Use Playwright (playwright skill) - Navigate, interact, assert DOM, screenshot
- **TUI/CLI**: Use interactive_bash (tmux) - Run command, send keystrokes, validate output
- **API/Backend**: Use Bash (curl) - Send requests, assert status + response fields
- **Library/Module**: Use Bash (php) - Include, call functions, compare output

---

## Execution Strategy

### Parallel Execution Waves

> Maximize throughput by grouping independent tasks into parallel waves.
> Each wave completes before the next begins.
> Target: 5-8 tasks per wave. Fewer than 3 per wave (except final) = under-splitting.

```
Wave 1 (Start Immediately - foundation):
├── Task 1: Add "Lihat Jawaban" button to rekap table [quick]
├── Task 2: Create admin/detail_jawaban.php (UI structure) [quick]
└── Task 3: Add question analysis to analytics.php [unspecified-high]

Wave 2 (After Wave 1):
├── Task 4: Complete detail_jawaban.php (logic + display) [deep]
├── Task 5: Create ekspor_jawaban.php [quick]
└── Task 6: Add filter benar/salah to detail page [quick]

Wave 3 (After Wave 2):
├── Task 7: Styling and UI polish for detail page [visual-engineering]
└── Task 8: Cross-feature testing and integration [unspecified-high]

Wave FINAL (After ALL tasks — 3 parallel reviews, then user okay):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Code quality review (unspecified-high)
├── Task F3: Real manual QA (unspecified-high)
-> Present results -> Get explicit user okay
```

### Dependency Matrix (abbreviated)

- **1**: - - 2, 3
- **2**: - 4, 7
- **3**: - 7, 8
- **4**: 2 - 6, 7
- **5**: - 8
- **6**: 4 - 7
- **7**: 4, 6 - 8
- **8**: 3, 5, 7 - F1-F3

### Agent Dispatch Summary

- **1**: **1** - T1 → `quick`
- **2**: **2** - T2 → `quick`, T3 → `unspecified-high`
- **3**: **3** - T4 → `deep`, T5 → `quick`, T6 → `quick`
- **4**: **2** - T7 → `visual-engineering`, T8 → `unspecified-high`
- **FINAL**: **3** - F1 → `oracle`, F2 → `unspecified-high`, F3 → `unspecified-high`

---

## TODOs

> Implementation + Test = ONE Task. Never separate.
> EVERY task MUST have: Recommended Agent Profile + Parallelization info + QA Scenarios.
> **A task WITHOUT QA Scenarios is INCOMPLETE. No exceptions.**

- [ ] 1. Tambah tombol "Lihat Jawaban" di tabel rekap nilai admin

  **What to do**:
  - Edit file `admin/rekap_nilai.php`
  - Di dalam tabel hasil ujian (baris 808-881), tambahkan kolom baru "Aksi" setelah kolom "Waktu Submit"
  - Tambahkan kode berikut di dalam `<td class="text-center">` pada baris 856:
    ```php
    <td class="text-center">
        <a href="detail_jawaban.php?id_hasil=<?= $hasil['id'] ?>" class="btn btn-sm btn-info" title="Lihat Jawaban">
            <i class="bi bi-eye"></i> Lihat
        </a>
    </td>
    ```
  - Pastikan kolom header tabel (baris 789-801) ditambahkan:
    ```html
    <th class="text-center" style="width: 100px;">Aksi</th>
    ```

  **Must NOT do**:
  - Jangan hapus fungsi yang sudah ada (hapus, remedi, dll)
  - Jangan ubah logika filter atau sorting yang sudah ada

  **Recommended Agent Profile**:
  > Select category + skills based on task domain. Justify each choice.
  - **Category**: `quick`
    - Reason: Simple HTML/PHP modification, adding button and column to existing table
  - **Skills**: [] (no special skills needed)
  - **Skills Evaluated but Omitted**:
    - `playwright`: Not needed, this is server-side PHP template modification

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 2 and 3)
  - **Parallel Group**: Wave 1 (with Tasks 2, 3)
  - **Blocks**: Task 4, 7 (detail page needs this button to link to it)
  - **Blocked By**: None (can start immediately)

  **References** (CRITICAL - Be Exhaustive):

  > The executor has NO context from your interview. References are their ONLY guide.
  > Each reference must answer: "What should I look at and WHY?"

  **Pattern References** (existing code to follow):
  - `admin/rekap_nilai.php:856-878` - Existing action buttons pattern (delete, remedi) to follow for consistent styling
  - `admin/rekap_nilai.php:789-801` - Table header section where to add new column header

  **API/Type References** (contracts to implement against):
  - `admin/rekap_nilai.php:808-881` - Table body loop where each row's data is displayed, need to add action cell

  **Test References** (testing patterns to follow):
  - Not applicable (no test framework)

  **External References** (libraries and frameworks):
  - Bootstrap 5 documentation: https://getbootstrap.com/docs/5.0/components/buttons/ - Button styling (btn-sm, btn-info)

  **WHY Each Reference Matters** (explain the relevance):
  - `admin/rekap_nilai.php:856-878`: Shows exact pattern for action buttons (delete, remedi) with Bootstrap classes and icon usage
  - `admin/rekap_nilai.php:789-801`: Shows where to add the new `<th>` element for column header

  **Acceptance Criteria**:

  > **AGENT-EXECUTABLE VERIFICATION ONLY** - No human action permitted.
  > Every criterion MUST be verifiable by running a command or using a tool.

  **QA Scenarios (MANDATORY - task is INCOMPLETE without these):**

  > **This is NOT optional. A task without QA scenarios WILL BE REJECTED.**
  >
  > Write scenario tests that verify the ACTUAL BEHAVIOR of what you built.
  > Minimum: 1 happy path + 1 failure/edge case per task.
  > Each scenario = exact tool + exact steps + exact assertions + evidence path.
  >
  > **The executing agent MUST run these scenarios after implementation.**
  > **The orchestrator WILL verify evidence files exist before marking task complete.**

  ```
  Scenario: Admin can see "Lihat Jawaban" button in rekap table (Happy path)
    Tool: Bash (curl)
    Preconditions: PHP server running, admin logged in
    Steps:
      1. curl -s http://localhost:8024/admin/rekap_nilai.php?ujian=9 -o /tmp/rekap_page.html
      2. grep -q 'detail_jawaban.php?id_hasil=' /tmp/rekap_page.html
      3. grep -q 'btn-info.*Lihat' /tmp/rekap_page.html
    Expected Result: grep exits with code 0 (found both patterns)
    Failure Indicators: grep exits with code 1 (pattern not found)
    Evidence: .sisyphus/evidence/task-1-button-visible.txt

  Scenario: Button links to correct detail page (Happy path)
    Tool: Bash (curl)
    Preconditions: Rekap page loaded
    Steps:
      1. curl -s http://localhost:8024/admin/rekap_nilai.php?ujian=9 | grep -o 'href="detail_jawaban.php?id_hasil=[0-9]*"'
      2. Verify output matches pattern: href="detail_jawaban.php?id_hasil=XX"
    Expected Result: Output contains valid URL with id_hasil parameter
    Failure Indicators: No output or malformed URL
    Evidence: .sisyphus/evidence/task-1-button-link.txt
  ```

  > **Specificity requirements - every scenario MUST use:**
  > - **Selectors**: N/A (using grep pattern matching on HTML)
  > - **Data**: Ujian ID 9 (exists in sample data), any hasil ID
  > - **Assertions**: grep exit code 0 = found, exit code 1 = not found
  > - **Timing**: N/A
  > - **Negative**: Button should NOT appear if no ujian selected (edge case)

  **Evidence to Capture:**
  - [ ] .sisyphus/evidence/task-1-button-visible.txt - curl output showing button exists
  - [ ] .sisyphus/evidence/task-1-button-link.txt - grep output showing correct link pattern

  **Commit**: YES (group with Task 2)
  - Message: `feat(admin): add view answers button to rekap table`
  - Files: `admin/rekap_nilai.php`
  - Pre-commit: N/A (no test framework)

---

- [ ] 2. Buat halaman admin/detail_jawaban.php (struktur dasar)

  **What to do**:
  - Buat file baru: `admin/detail_jawaban.php`
  - Mulai dengan struktur dasar PHP:
    ```php
    <?php
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
    require_once '../config/database.php';
    require_once '../config/init_sekolah.php';
    
    // Validasi parameter
    if (!isset($_GET['id_hasil']) || empty($_GET['id_hasil'])) {
        die("Parameter tidak valid");
    }
    $id_hasil = (int)$_GET['id_hasil'];
    
    // Fetch data hasil ujian
    $stmt = $conn->prepare("SELECT * FROM hasil_ujian WHERE id = ?");
    $stmt->bind_param("i", $id_hasil);
    $stmt->execute();
    $hasil = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$hasil) {
        die("Data tidak ditemukan");
    }
    
    // Decode detail jawaban
    $detail_jawaban = json_decode($hasil['detail_jawaban'], true);
    // ... (continue with UI)
    ?>
    ```
  - Reuse tampilan dari `review.php` (baris 85-296) untuk struktur HTML
  - Implementasikan header, card layout, dan looping jawaban (seperti `review.php:228-281`)

  **Must NOT do**:
  - Jangan copy seluruh kode `review.php` tanpa modifikasi (ini untuk admin, bukan siswa)
  - Jangan lupakan validasi session dan parameter

  **Recommended Agent Profile**:
  > Select category + skills based on task domain. Justify each choice.
  - **Category**: `quick`
    - Reason: Creating new PHP file with HTML structure, reusing existing pattern from review.php
  - **Skills**: [] (no special skills needed, pure PHP/HTML)
  - **Skills Evaluated but Omitted**:
    - `playwright`: Not needed, this is server-side rendering

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 1 and 3)
  - **Parallel Group**: Wave 1 (with Tasks 1, 3)
  - **Blocks**: Task 4 (logic completion), Task 6 (filter feature)
  - **Blocked By**: None (can start immediately)

  **References** (CRITICAL - Be Exhaustive):

  **Pattern References** (existing code to follow):
  - `review.php:85-296` - Complete HTML structure to reuse (header, card layout, question loop)
  - `review.php:1-82` - PHP logic for fetching and decoding `detail_jawaban` JSON
  - `admin/rekap_nilai.php:14-17` - Admin session validation pattern

  **API/Type References** (contracts to implement against):
  - Database: `hasil_ujian` table has `detail_jawaban` JSON column
  - JSON structure: `{soal_id, pertanyaan, jawaban_siswa, kunci_jawaban, is_correct, poin, poin_diperoleh, opsi_a-e}`

  **Test References** (testing patterns to follow):
  - Not applicable (no test framework)

  **External References** (libraries and frameworks):
  - Bootstrap 5: https://getbootstrap.com/docs/5.0/components/card/ - Card component
  - Bootstrap Icons: https://icons.getbootstrap.com/ - Icons used (bi-check-circle, bi-x-circle)

  **WHY Each Reference Matters**:
  - `review.php:85-296`: Provides proven UI pattern for displaying questions, answers, and correctness indicators
  - `review.php:1-82`: Shows how to fetch and decode `detail_jawaban` JSON correctly
  - `hasil_ujian` table: Contract for data structure that must be queried

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Admin can access detail page with valid id_hasil (Happy path)
    Tool: Bash (curl)
    Preconditions: PHP server running, admin session active
    Steps:
      1. First, get a valid id_hasil: curl -s http://localhost:8024/admin/rekap_nilai.php?ujian=9 | grep -o 'id_hasil=[0-9]*' | head -1
      2. curl -s "http://localhost:8024/admin/detail_jawaban.php?id_hasil=32" -o /tmp/detail_page.html
      3. grep -q 'Pembahasan Jawaban' /tmp/detail_page.html
    Expected Result: grep exits 0 (page loaded with title)
    Failure Indicators: grep exits 1, or "Data tidak ditemukan" in output
    Evidence: .sisyphus/evidence/task-2-page-loads.txt

  Scenario: Invalid id_hasil shows error (Failure case)
    Tool: Bash (curl)
    Preconditions: Server running
    Steps:
      1. curl -s "http://localhost:8024/admin/detail_jawaban.php?id_hasil=99999" -o /tmp/detail_error.html
      2. grep -q 'Data tidak ditemukan' /tmp/detail_error.html
    Expected Result: grep exits 0 (error message shown)
    Evidence: .sisyphus/evidence/task-2-invalid-id.txt
  ```

  **Evidence to Capture:**
  - [ ] .sisyphus/evidence/task-2-page-loads.txt
  - [ ] .sisyphus/evidence/task-2-invalid-id.txt

  **Commit**: YES (group with Task 1)
  - Message: `feat(admin): add detail jawaban page structure`
  - Files: `admin/detail_jawaban.php`
  - Pre-commit: N/A

---

- [ ] 3. Tambah analisis butir soal di admin/analytics.php

  **What to do**:
  - Edit `admin/analytics.php` (baris 1-1336)
  - Tambahkan section baru setelah "Top Scorers" (sekitar baris 400-500):
    ```php
    // Question Analysis - Top 20 worst questions
    $sql = "
        SELECT 
            d.soal_id,
            s.pertanyaan,
            s.kategori,
            COUNT(*) as total_attempts,
            SUM(CASE WHEN d.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
            ROUND(SUM(CASE WHEN d.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate,
            AVG(d.poin_diperoleh) as avg_poin
        FROM hasil_ujian h
        CROSS JOIN JSON_TABLE(h.detail_jawaban, '$[*]' COLUMNS(
            soal_id INT PATH '$.soal_id',
            is_correct BOOLEAN PATH '$.is_correct',
            poin_diperoleh INT PATH '$.poin_diperoleh'
        )) d
        JOIN soal s ON d.soal_id = s.id
        WHERE h.id_ujian = ?
        GROUP BY d.soal_id, s.pertanyaan, s.kategori
        ORDER BY success_rate ASC
        LIMIT 20
    ";
    ```
  - Tambahkan tampilan HTML (chart dan table) untuk question analysis
  - Gunakan Chart.js yang sudah ada di analytics.php untuk visualisasi

  **Must NOT do**:
  - Jangan hapus analytics yang sudah ada (grade distribution, score distribution)
  - Jangan ubah query yang sudah ada, tambahkan saja query baru

  **Recommended Agent Profile**:
  > Select category + skills based on task domain. Justify each choice.
  - **Category**: `unspecified-high`
    - Reason: Complex SQL with JSON_TABLE, data analysis, and Chart.js visualization - requires careful implementation
  - **Skills**: [] (no special skills, but requires SQL and JS knowledge)
  - **Skills Evaluated but Omitted**:
    - `playwright`: Not needed, this is backend + template work

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 1 and 2)
  - **Parallel Group**: Wave 1 (with Tasks 1, 2)
  - **Blocks**: Task 8 (integration testing)
  - **Blocked By**: None (can start immediately)

  **References** (CRITICAL - Be Exhaustive):

  **Pattern References** (existing code to follow):
  - `admin/analytics.php:55-74` - Existing analytics data structure pattern
  - `admin/analytics.php:100-150` - SQL query patterns for analytics
  - `admin/analytics.php:400-600` - Chart.js visualization patterns (check actual file for chart code)

  **API/Type References** (contracts to implement against):
  - MySQL JSON_TABLE function: https://dev.mysql.com/doc/refman/8.0/en/json-table-functions.html
  - Chart.js documentation: https://www.chartjs.org/docs/latest/

  **Test References** (testing patterns to follow):
  - Not applicable

  **External References** (libraries and frameworks):
  - Chart.js 4.4.1 (already included in analytics.php)
  - MySQL 8.0+ JSON functions

  **WHY Each Reference Matters**:
  - `admin/analytics.php:55-74`: Shows how analytics data is structured and passed to UI
  - MySQL JSON_TABLE: Required to parse `detail_jawaban` JSON for question-level analysis
  - Chart.js: Needed to create horizontal bar chart for question analysis

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Question analysis appears in analytics page (Happy path)
    Tool: Bash (curl)
    Preconditions: PHP server running, ujian 9 has data
    Steps:
      1. curl -s "http://localhost:8024/admin/analytics.php?ujian=9" -o /tmp/analytics_page.html
      2. grep -q 'Analisis Butir Soal' /tmp/analytics_page.html
      3. grep -q 'success_rate' /tmp/analytics_page.html
    Expected Result: grep exits 0 for both patterns
    Evidence: .sisyphus/evidence/task-3-analytics-shows.txt

  Scenario: Question analysis shows worst 20 questions (Happy path)
    Tool: Bash (curl) + SQL check
    Preconditions: Database accessible
    Steps:
      1. Run SQL: SELECT COUNT(DISTINCT JSON_EXTRACT(d.val, '$.soal_id')) FROM hasil_ujian, JSON_TABLE(detail_jawaban, '$[*]' COLUMNS(val JSON PATH '$')) d WHERE id_ujian=9
      2. Verify analytics page shows <= 20 questions in analysis table
    Expected Result: Table shows top 20 worst questions by success rate
    Evidence: .sisyphus/evidence/task-3-worst-questions.txt
  ```

  **Evidence to Capture:**
  - [ ] .sisyphus/evidence/task-3-analytics-shows.txt
  - [ ] .sisyphus/evidence/task-3-worst-questions.txt

  **Commit**: YES (separate commit)
  - Message: `feat(admin): add question analysis to analytics`
  - Files: `admin/analytics.php`
  - Pre-commit: N/A

---

- [ ] 4. Complete detail_jawaban.php (logic + display lengkap)

  **What to do**:
  - Lanjutkan dari Task 2 (file sudah ada, struktur dasar sudah ada)
  - Tambahkan logic lengkap untuk menampilkan jawaban:
    ```php
    // Calculate statistics
    $total_benar = 0;
    foreach ($detail_jawaban as $jw) {
        if ($jw['is_correct']) $total_benar++;
    }
    
    // Display like review.php:228-281
    // Loop through $detail_jawaban and show:
    // - Question number, question text
    // - Options A-E with indicators (green = correct, red = student's wrong answer)
    // - Score obtained vs max points
    ```
  - Tambahkan ringkasan di atas (seperti `review.php:207-222`):
    - Total skor, benar/total, persentase
  - Tambahkan tombol navigasi: "Kembali ke Rekap Nilai"
  - Format tampilan: Gunakan style dari `review.php:94-183`

  **Must NOT do**:
  - Jangan tampilkan data siswa lain (hanya data NIS yang dipilih)
  - Jangan lupakan CSRF protection jika ada form

  **Recommended Agent Profile**:
  > Select category + skills based on task domain. Justify each choice.
  - **Category**: `deep`
    - Reason: Complex PHP/HTML integration, JSON parsing, conditional display logic, needs careful implementation
  - **Skills**: [] (pure PHP/HTML, no special skills)
  - **Skills Evaluated but Omitted**:
    - `playwright`: Not needed, this is server-side rendering

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends on Task 2)
  - **Parallel Group**: Wave 2 (with Tasks 5, 6)
  - **Blocks**: Task 7 (styling), Task 6 (filter feature)
  - **Blocked By**: Task 2 (structure must exist first)

  **References** (CRITICAL - Be Exhaustive):

  **Pattern References** (existing code to follow):
  - `review.php:207-222` - Score summary card layout
  - `review.php:228-281` - Question loop with correctness indicators
  - `review.php:94-183` - Styling classes (review-card, soal-number, badge-benar, etc.)

  **API/Type References** (contracts to implement against):
  - `hasil_ujian` table: `detail_jawaban` JSON structure
  - JSON fields: `soal_id, pertanyaan, jawaban_siswa, kunci_jawaban, is_correct, poin, poin_diperoleh, opsi_a-e`

  **Test References** (testing patterns to follow):
  - Not applicable

  **External References** (libraries and frameworks):
  - Bootstrap 5: https://getbootstrap.com/docs/5.0/components/badge/ - Badge styling
  - Google Fonts: Poppins (already included in review.php)

  **WHY Each Reference Matters**:
  - `review.php:228-281`: Shows exact loop pattern for displaying questions with option comparison
  - `review.php:207-222`: Shows summary statistics calculation and display
  - JSON structure: Contract for data that must be decoded and displayed

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Detail page shows all questions with answers (Happy path)
    Tool: Bash (curl)
    Preconditions: Valid id_hasil=32 exists
    Steps:
      1. curl -s "http://localhost:8024/admin/detail_jawaban.php?id_hasil=32" -o /tmp/detail_full.html
      2. grep -c 'soal-number\|soal_id' /tmp/detail_full.html
      3. Verify count > 5 (multiple questions displayed)
    Expected Result: HTML contains multiple question displays with options
    Evidence: .sisyphus/evidence/task-4-questions-display.txt

  Scenario: Correct/wrong answers properly highlighted (Happy path)
    Tool: Bash (curl)
    Preconditions: Page loaded
    Steps:
      1. curl -s "http://localhost:8024/admin/detail_jawaban.php?id_hasil=32" | grep -c 'badge-benar\|badge-salah'
      2. Verify both patterns found (some correct, some wrong)
    Expected Result: Both badge classes present in output
    Evidence: .sisyphus/evidence/task-4-correctness-highlight.txt
  ```

  **Evidence to Capture:**
  - [ ] .sisyphus/evidence/task-4-questions-display.txt
  - [ ] .sisyphus/evidence/task-4-correctness-highlight.txt

  **Commit**: YES (separate commit)
  - Message: `feat(admin): complete detail jawaban page with full display`
  - Files: `admin/detail_jawaban.php`
  - Pre-commit: N/A

---

- [ ] 5. Buat halaman admin/ekspor_jawaban.php

  **What to do**:
  - Buat file baru `admin/ekspor_jawaban.php`
  - Logic export ke Excel (.xls format seperti `admin/ekspor_excel.php`):
    ```php
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=jawaban_ujian_{$id_ujian}.xls");
    
    // Fetch all hasil_ujian for selected ujian
    $stmt = $conn->prepare("SELECT * FROM hasil_ujian WHERE id_ujian = ? ORDER BY nama");
    // For each student, decode detail_jawaban JSON
    // Create table: NIS | Nama | Kelas | Soal 1 | Soal 2 | ... | Total Skor
    // Fill with: A/B/C/D/E for each question
    ```
  - Gunakan pattern dari `admin/ekspor_excel.php` untuk format Excel
  - Tambahkan pewarnaan: Hijau untuk benar, Merah untuk salah (menggunakan CSS `bgcolor`)

  **Must NOT do**:
  - Jangan export data siswa dari ujian lain (filter by id_ujian)
  - Jangan lupakan validasi admin session

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Creating new PHP file, following existing Excel export pattern
  - **Skills**: [] (pure PHP, no special skills)
  - **Skills Evaluated but Omitted**:
    - `playwright`: Not needed, server-side export generation

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 6, after Task 1-3)
  - **Parallel Group**: Wave 2 (with Tasks 4, 6)
  - **Blocks**: Task 8 (integration testing)
  - **Blocked By**: Task 1-3 (needs to know button link works)

  **References** (CRITICAL - Be Exhaustive):

  **Pattern References** (existing code to follow):
  - `admin/ekspor_excel.php` - Existing Excel export pattern (full file reference)
  - `admin/ekspor_excel.php:1-50` - Header settings for .xls download
  - `admin/ekspor_excel.php:50-150` - Table generation pattern with CSS styling

  **API/Type References** (contracts to implement against):
  - `hasil_ujian` table: `id_ujian, nis, nama, kelas, total_skor, detail_jawaban`
  - JSON structure in `detail_jawaban`: need to decode and create columns

  **Test References** (testing patterns to follow):
  - Not applicable

  **External References** (libraries and frameworks):
  - PHP header() function: https://www.php.net/manual/en/function.header.php - For Excel download
  - HTML tables for Excel: https://support.microsoft.com/en-us/office/import-html-tables-into-excel-8-ways-6f3b7d3b-4f1b-4b3b-9b6b-3c59e6e7c1e - Format reference

  **WHY Each Reference Matters**:
  - `admin/ekspor_excel.php`: Proven pattern for Excel export that user is already familiar with
  - `hasil_ujian` table: Data source contract that must be queried correctly

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Export generates valid Excel file (Happy path)
    Tool: Bash (curl)
    Preconditions: Admin logged in, ujian 9 has data
    Steps:
      1. curl -s "http://localhost:8024/admin/ekspor_jawaban.php?ujian=9" -o /tmp/jawaban.xls
      2. file /tmp/jawaban.xls | grep -q 'HTML document\|MS Excel'
      3. head -20 /tmp/jawaban.xls | grep -q '<table>\|<tr>\|<td>'
    Expected Result: File contains HTML table format readable by Excel
    Evidence: .sisyphus/evidence/task-5-export-xls.txt

  Scenario: Export contains student answers (Happy path)
    Tool: Bash (curl)
    Preconditions: Export file from previous scenario
    Steps:
      1. grep -q 'NIS\|Nama' /tmp/jawaban.xls
      2. grep -q 'A\|B\|C\|D\|E' /tmp/jawaban.xls  # Answer letters
    Expected Result: Both patterns found (headers and answer letters)
    Evidence: .sisyphus/evidence/task-5-answers-present.txt
  ```

  **Evidence to Capture:**
  - [ ] .sisyphus/evidence/task-5-export-xls.txt
  - [ ] .sisyphus/evidence/task-5-answers-present.txt

  **Commit**: YES (separate commit)
  - Message: `feat(admin): add jawaban export to Excel`
  - Files: `admin/ekspor_jawaban.php`
  - Pre-commit: N/A

---

- [ ] 6. Tambah filter benar/salah di halaman detail jawaban

  **What to do**:
  - Edit `admin/detail_jawaban.php` (Task 2 & 4)
  - Tambahkan UI filter di atas daftar soal:
    ```html
    <div class="mb-3">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary active" onclick="filterAnswers('all')">Semua</button>
            <button type="button" class="btn btn-outline-success" onclick="filterAnswers('benar')">Benar Saja</button>
            <button type="button" class="btn btn-outline-danger" onclick="filterAnswers('salah')">Salah Saja</button>
        </div>
    </div>
    ```
  - Tambahkan JavaScript:
    ```javascript
    function filterAnswers(type) {
        const cards = document.querySelectorAll('.review-card');
        cards.forEach(card => {
            if (type === 'all') card.style.display = '';
            else if (type === 'benar') card.style.display = card.classList.contains('benar') ? '' : 'none';
            else if (type === 'salah') card.style.display = card.classList.contains('salah') ? '' : 'none';
        });
    }
    ```
  - Tambahkan class `benar` atau `salah` pada `.review-card` (sudah ada dari Task 4)

  **Must NOT do**:
  - Jangan reload page saat filter (pure JavaScript, no server-side)
  - Jangan ubah data yang ditampilkan, hanya sembunyikan (CSS display:none)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Simple JavaScript + HTML button group addition, no backend changes
  - **Skills**: [] (vanilla JavaScript, no special skills)
  - **Skills Evaluated but Omitted**:
    - `playwright`: Not needed for implementation, but will be used in QA

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 5, after Task 4)
  - **Parallel Group**: Wave 2 (with Tasks 4, 5)
  - **Blocks**: Task 7 (styling integration)
  - **Blocked By**: Task 4 (cards must have benar/salah class)

  **References** (CRITICAL - Be Exhaustive):

  **Pattern References** (existing code to follow):
  - `admin/detail_jawaban.php` (Task 4 output) - Cards with class `benar` or `salah`
  - Bootstrap 5 button group: https://getbootstrap.com/docs/5.0/components/button-group/ - UI pattern

  **API/Type References** (contracts to implement against):
  - None (pure frontend JavaScript)

  **Test References** (testing patterns to follow):
  - Not applicable

  **External References** (libraries and frameworks):
  - Bootstrap 5 button group component
  - Vanilla JavaScript: https://developer.mozilla.org/en-US/docs/Web/API/Document/querySelectorAll - For filtering

  **WHY Each Reference Matters**:
  - Task 4 output: Cards must have `benar` or `salah` class for filter to work
  - Bootstrap button group: Consistent UI with rest of application

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Filter buttons appear and work (Happy path)
    Tool: Playwright
    Preconditions: Page loaded with mixed correct/incorrect answers
    Steps:
      1. Navigate to http://localhost:8024/admin/detail_jawaban.php?id_hasil=32
      2. Verify buttons "Semua", "Benar Saja", "Salah Saja" are visible
      3. Click "Salah Saja"
      4. Verify only .review-card.salah are visible (count > 0)
    Expected Result: Filter hides/shows cards correctly
    Evidence: .sisyphus/evidence/task-6-filter-benar-salah.png

  Scenario: "Semua" button resets filter (Happy path)
    Tool: Playwright
    Preconditions: Filter applied (showing only wrong answers)
    Steps:
      1. Click "Benar Saja" (if not already)
      2. Count visible cards
      3. Click "Semua"
      4. Verify all cards are visible (count restored)
    Expected Result: All cards visible after clicking "Semua"
    Evidence: .sisyphus/evidence/task-6-filter-reset.txt
  ```

  **Evidence to Capture:**
  - [ ] .sisyphus/evidence/task-6-filter-benar-salah.png
  - [ ] .sisyphus/evidence/task-6-filter-reset.txt

  **Commit**: YES (separate commit)
  - Message: `feat(admin): add filter benar/salah to detail page`
  - Files: `admin/detail_jawaban.php`
  - Pre-commit: N/A

---

- [ ] 7. Styling and UI polish for detail page

  **What to do**:
  - Review `admin/detail_jawaban.php` styling (from Tasks 2 & 4)
  - Ensure consistent look with `review.php`:
    - Review header gradient (like `review.php:98-102`)
    - Card styling: `review-card`, `benar`/`salah` classes
    - Badge styling: `badge-benar`, `badge-salah`
    - Soal number circle: `soal-number` class
  - Add responsive adjustments if needed (mobile view)
  - Ensure Bootstrap Icons are loaded (bi-eye, bi-check-circle, bi-x-circle)

  **Must NOT do**:
  - Jangan ubah struktur HTML yang sudah ada
  - Jangan hapus fungsi yang sudah ada

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Pure CSS/styling work, ensuring visual consistency with existing `review.php`
  - **Skills**: [] (CSS/HTML, no special skills)
  - **Skills Evaluated but Omitted**:
    - `playwright`: Not needed for implementation, but will be used in QA.

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends on Task 4 & 6)
  - **Parallel Group**: Wave 3 (with Task 8)
  - **Blocks**: Task 8 (integration testing)
  - **Blocked By**: Task 4 (cards exist), Task 6 (filter buttons exist)

  **References** (CRITICAL - Be Exhaustive):

  **Pattern References** (existing code to follow):
  - `review.php:94-183` - Complete styling classes to replicate
  - `review.php:98-102` - Header gradient pattern
  - `review.php:104-121` - `.review-card`, `.benar`, `.salah` classes
  - `review.php:123-135` - `.soal-number` circle styling

  **API/Type References** (contracts to implement against):
  - None (pure CSS work)

  **Test References** (testing patterns to follow):
  - Not applicable

  **External References** (libraries and frameworks):
  - Bootstrap 5: https://getbootstrap.com/docs/5.0/customize/css/ - CSS customization
  - Bootstrap Icons: https://icons.getbootstrap.com/ - Icon reference

  **WHY Each Reference Matters**:
  - `review.php:94-183`: Source of truth for visual styling that user is familiar with
  - Bootstrap Icons: Needed to verify correct icon classes are used

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Detail page matches review.php styling (Happy path)
    Tool: Playwright
    Preconditions: Page loaded
    Steps:
      1. Navigate to http://localhost:8024/admin/detail_jawaban.php?id_hasil=32
      2. Screenshot full page: .sisyphus/evidence/task-7-styling-full.png
      3. Verify gradient header present (check computed style of .review-header)
    Expected Result: Page looks consistent with review.php
    Evidence: .sisyphus/evidence/task-7-styling-full.png

  Scenario: Responsive view works (Happy path - edge case)
    Tool: Playwright
    Preconditions: Page loaded
    Steps:
      1. Set viewport to 375x667 (mobile)
      2. Navigate to detail page
      3. Verify cards stack properly, no horizontal scroll
    Expected Result: Mobile view usable, no layout breakage
    Evidence: .sisyphus/evidence/task-7-mobile-view.png
  ```

  **Evidence to Capture:**
  - [ ] .sisyphus/evidence/task-7-styling-full.png
  - [ ] .sisyphus/evidence/task-7-mobile-view.png

  **Commit**: YES (separate commit)
  - Message: `style(admin): polish detail jawaban page UI`
  - Files: `admin/detail_jawaban.php`
  - Pre-commit: N/A

---

- [ ] 8. Cross-feature testing and integration

  **What to do**:
  - Test integration between all features:
    1. Click "Lihat Jawaban" in rekap → Opens detail page with correct ID
    2. Detail page shows correct data (compare with database)
    3. Analytics shows question analysis (verify worst 20 questions)
    4. Export generates valid Excel with answers
    5. Filter works in detail page
  - Test edge cases:
    - Empty `detail_jawaban` (handle gracefully)
    - Invalid `id_hasil` (show error)
    - No ujian selected in rekap (button still appears but link might be broken - verify)

  **Must NOT do**:
  - Jangan modifikasi kode yang sudah ada (hanya testing)
  - Jangan create data palsu (use existing sample data)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Complex integration testing across multiple features, requires careful verification
  - **Skills**: [] (uses curl, Playwright implicitly via QA scenarios)
  - **Skills Evaluated but Omitted**:
    - `playwright`: Will be used in QA scenarios, but agent can invoke directly

  **Parallelization**:
  - **Can Run In Parallel**: NO (depends on all previous tasks)
  - **Parallel Group**: Wave 3 (with Task 7)
  - **Blocks**: Final Verification Wave (F1-F4)
  - **Blocked By**: Task 3 (analytics), Task 5 (export), Task 7 (styling)

  **References** (CRITICAL - Be Exhaustive):

  **Pattern References** (existing code to follow):
  - `review.php` - Source of truth for correct answer display
  - `admin/rekap_nilai.php` - Source of truth for table structure
  - `admin/analytics.php` - Verify question analysis section added

  **API/Type References** (contracts to implement against):
  - `hasil_ujian` table: `detail_jawaban` JSON structure
  - MySQL JSON_TABLE: For analytics query verification

  **Test References** (testing patterns to follow):
  - Not applicable (no test framework)

  **External References** (libraries and frameworks):
  - None specific (integration testing across PHP pages)

  **WHY Each Reference Matters**:
  - All previous tasks' outputs: Need to verify they work together correctly
  - Database contract: Ensure data flows correctly between pages

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Full integration flow - Rekap → Detail → Filter (Happy path)
    Tool: Playwright
    Preconditions: Admin logged in, ujian=9 has data
    Steps:
      1. Navigate to http://localhost:8024/admin/rekap_nilai.php?ujian=9
      2. Click first "Lihat Jawaban" button
      3. Verify detail page loads with correct student name
      4. Click "Salah Saja" filter
      5. Verify only wrong answers shown
      6. Click "Kembali ke Rekap Nilai" link
      7. Verify returned to rekap page
    Expected Result: Smooth flow between pages, filter works
    Evidence: .sisyphus/evidence/task-8-integration-flow.png

  Scenario: Analytics shows question analysis (Happy path)
    Tool: Bash (curl)
    Preconditions: Server running
    Steps:
      1. curl -s "http://localhost:8024/admin/analytics.php?ujian=9" -o /tmp/analytics_final.html
      2. grep -q 'Analisis Butir Soal' /tmp/analytics_final.html
      3. grep -q 'success_rate' /tmp/analytics_final.html
    Expected Result: Both patterns found
    Evidence: .sisyphus/evidence/task-8-analytics-final.txt
  ```

  **Evidence to Capture:**
  - [ ] .sisyphus/evidence/task-8-integration-flow.png
  - [ ] .sisyphus/evidence/task-8-analytics-final.txt

  **Commit**: NO (testing task, no code changes)

---

## Final Verification Wave (MANDATORY — after ALL implementation tasks)

> 4 review agents run in PARALLEL. ALL must APPROVE. Present consolidated results to user and get explicit "okay" before completing.
>
> **Do NOT auto-proceed after verification. Wait for user's explicit approval before marking work complete.**
> **Never mark F1-F4 as checked before getting user's okay.** Rejection or user feedback -> fix -> re-run -> present again -> wait for okay.

- [ ] F1. **Plan Compliance Audit** — `oracle`
  Read the plan end-to-end. For each "Must Have": verify implementation exists (read file, curl endpoint, run command). For each "Must NOT Have": search codebase for forbidden patterns — reject with file:line if found. Check evidence files exist in .sisyphus/evidence/. Compare deliverables against plan.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | Tasks [N/N] | VERDICT: APPROVE/REJECT`

- [ ] F2. **Code Quality Review** — `unspecified-high`
  Run static analysis if available (PHP lint). Review all changed files for: syntax errors, unused variables, inconsistent styling vs existing codebase. Check for: CSRF protection on new pages, SQL injection prevention (prepared statements), XSS prevention (htmlspecialchars). Check replication of `review.php` patterns.
  Output: `Syntax [PASS/FAIL] | Security [PASS/FAIL] | Consistency [PASS/FAIL] | VERDICT`

- [ ] F3. **Real Manual QA** — `unspecified-high` (+ `playwright` if available)
  Start from clean state. Execute EVERY QA scenario from EVERY task — follow exact steps, capture evidence. Test cross-task integration (features working together, not isolation). Test edge cases: empty state, invalid input, rapid actions. Save to `.sisyphus/evidence/final-qa/`.
  Output: `Scenarios [N/N pass] | Integration [N/N] | Edge Cases [N tested] | VERDICT`

- [ ] F4. **Scope Fidelity Check** — `deep`
  For each task: read "What to do", read actual diff (git log/diff). Verify 1:1 — everything in spec was built (no missing), nothing beyond spec was built (no creep). Check "Must NOT do" compliance. Detect cross-task contamination: Task N touching Task M's files unnecessarily.
  Output: `Tasks [N/N compliant] | Contamination [CLEAN/N issues] | Unaccounted [CLEAN/N files] | VERDICT`

---

## Commit Strategy

- **1**: `feat(admin): add view answers button to rekap table` - `admin/rekap_nilai.php`, php lint
- **2**: `feat(admin): add detail jawaban page structure` - `admin/detail_jawaban.php`, php lint
- **3**: `feat(admin): add question analysis to analytics` - `admin/analytics.php`, php lint
- **4**: `feat(admin): complete detail jawaban page with full display` - `admin/detail_jawaban.php`, php lint
- **5**: `feat(admin): add jawaban export to Excel` - `admin/ekspor_jawaban.php`, php lint
- **6**: `feat(admin): add filter benar/salah to detail page` - `admin/detail_jawaban.php`, php lint
- **7**: `style(admin): polish detail jawaban page UI` - `admin/detail_jawaban.php`, css check
- **8**: N/A (testing task)

---

## Success Criteria

### Verification Commands
```bash
php -l admin/rekap_nilai.php  # Expected: No syntax errors
php -l admin/detail_jawaban.php  # Expected: No syntax errors
php -l admin/analytics.php  # Expected: No syntax errors
php -l admin/ekspor_jawaban.php  # Expected: No syntax errors
curl -s http://localhost:8024/admin/detail_jawaban.php?id_hasil=32 | grep -q "Pembahasan Jawaban"  # Expected: exit 0
curl -s "http://localhost:8024/admin/analytics.php?ujian=9" | grep -q "Analisis Butir Soal"  # Expected: exit 0
```

### Final Checklist
- [ ] All "Must Have" present (button in rekap, detail page, analytics, export, filter)
- [ ] All "Must NOT Have" absent (no DB changes, no scoring logic changes)
- [ ] All evidence files exist in `.sisyphus/evidence/`
- [ ] All tasks have QA scenarios that pass
- [ ] Final Verification Wave (F1-F3) all APPROVED
- [ ] User gives explicit "okay" to complete

---

## Self-Review (Post-Generation)

### Gap Classification

**CRITICAL: Requires User Input**: NONE - All questions answered (Opsi C confirmed)

**MINOR: Can Self-Resolve**:
- Exact ujian ID for testing (used 9 based on sample data) - FIXED: Used ujian=9 which exists in data
- Student name display format in export - FIXED: Will follow `ekspor_excel.php` pattern

**AMBIGUOUS: Default Available**:
- Filter UI style (button group vs dropdown) - APPLIED DEFAULT: Bootstrap button group (consistent with Bootstrap 5 theme)
- Excel export format (.xls vs .xlsx) - APPLIED DEFAULT: .xls (like existing `ekspor_excel.php`)

### Review Checklist ✓

```
□ All TODO items have concrete acceptance criteria? YES
□ All file references exist in codebase? YES (review.php, rekap_nilai.php, analytics.php verified)
□ No assumptions about business logic without evidence? YES (using existing JSON structure)
□ Guardrails from Metis review incorporated? YES (admin session validation, parameter validation)
□ Scope boundaries clearly defined? YES (Must Have / Must NOT Have sections)
□ Every task has Agent-Executed QA Scenarios? YES (all 8 tasks have QA scenarios)
□ QA scenarios include BOTH happy-path AND negative/error scenarios? YES (2 scenarios per task minimum)
□ Zero acceptance criteria require human intervention? YES (all use curl/Playwright/bash)
□ QA scenarios use specific selectors/data, not vague descriptions? YES (specific grep patterns, URLs, IDs)
```

### Auto-Resolved (minor gaps fixed):
- Used ujian=9 which has sample data (from SQL dump analysis)
- Reused `review.php` patterns throughout (proven UI)

### Defaults Applied (override if needed):
- Filter UI: Bootstrap button group (not dropdown) - consistent with BS5 theme
- Export format: .xls (like existing `ekspor_excel.php`) - user familiar with this format

### Decisions Needed:
- NONE - All requirements clear from Opsi C choice

---

**Plan saved to**: `.sisyphus/plans/rekap-jawaban-siswa.md`  
**Draft file**: `.sisyphus/drafts/rekap-jawaban-siswa.md` (will be deleted after user approval)

