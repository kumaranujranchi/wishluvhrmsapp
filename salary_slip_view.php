<?php
require_once 'config/db.php';
// IMPORTANT: Do NOT include header/footer here as this is a print-only page.

// Ensure login
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access");
}

$user_id = $_SESSION['user_id'];
$slip_id = $_GET['id'] ?? 0;

try {
    // Fetch Slip Data + Employee Data + Department + Designation
    $sql = "SELECT p.*, e.first_name, e.last_name, e.employee_code, e.joining_date,
            d.name as dept_name, des.name as designation_title
            FROM monthly_payroll p
            JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN designations des ON e.designation_id = des.id
            WHERE p.id = :id AND p.employee_id = :uid";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $slip_id, 'uid' => $user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("Salary slip not found or access denied.");
    }

    $dateObj = DateTime::createFromFormat('!m', $data['month']);
    $monthName = $dateObj->format('F');
    $payPeriod = $monthName . ' ' . $data['year'];

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?= $monthName ?> <?= $data['year'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 40px;
            color: #1e293b;
        }
        .a4-page {
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            box-sizing: border-box;
            border-top: 10px solid #1e3a8a; /* Professional Blue Top Border */
        }
        
        .content-padding {
            padding: 40px 50px;
        }

        /* HEADER SECTION */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header-logo img {
            max-height: 80px;
            max-width: 150px;
        }
        .company-info {
            text-align: right;
        }
        .company-name {
            font-size: 24px;
            font-weight: 700;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .company-address {
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
            max-width: 300px;
            margin-left: auto;
        }

        .slip-heading {
            text-align: center;
            background: #f8fafc;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }
        .slip-heading h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
        }
        .slip-heading span {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        /* EMPLOYEE GRID */
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 4px;
        }
        .info-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
        }
        .info-value {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        /* TABLE */
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 13px;
        }
        .salary-table th {
            background: #1e3a8a;
            color: white;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
            padding: 12px 15px;
            text-align: left;
        }
        .salary-table th.amount-head {
            text-align: right;
        }
        .salary-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 15px;
            color: #334155;
        }
        .salary-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .amount-col {
            text-align: right;
            font-weight: 600;
            font-family: 'Roboto Mono', monospace;
        }

        .total-row td {
            background:#f1f5f9;
            font-weight: 800;
            color: #1e3a8a;
            border-top: 2px solid #cbd5e1;
        }

        /* NET PAY CARD */
        .net-pay-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .words-section {
            flex: 1;
            padding-right: 20px;
        }
        .words-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #15803d;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .words-value {
            font-size: 13px;
            font-style: italic;
            color: #166534;
            font-weight: 500;
        }

        .net-amount-box {
            text-align: right;
        }
        .net-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #166534;
            font-weight: 700;
        }
        .net-value {
            font-size: 28px;
            font-weight: 800;
            color: #15803d;
            margin-top: 0;
            line-height: 1;
        }

        /* FOOTER */
        .slip-footer {
            margin-top: 60px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
        .footer-note {
            font-size: 10px;
            color: #94a3b8;
            margin-bottom: 5px;
        }
        .company-ref {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
        }

        /* PRINT BUTTON */
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1e3a8a;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(30, 58, 138, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            z-index: 1000;
        }
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(30, 58, 138, 0.5);
        }

        @media print {
            body { 
                background: white; 
                padding: 0; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .a4-page {
                box-shadow: none;
                margin: 0;
                width: 100%;
                max-width: 100%;
                border-top: none; /* Optional: remove colored border for simpler print */
            }
            .print-btn {
                display: none;
            }
            .content-padding {
                padding: 20px 40px;
            }
        }
    </style>
</head>
<body>

    <div class="a4-page">
        <div class="content-padding">
            
            <!-- HEADER -->
            <div class="header">
                <div class="header-logo">
                    <!-- Updated to use the actual asset if available, fallback to text -->
                    <img src="assets/logo.png" alt="Wishluv Buildcon" onerror="this.style.display='none'">
                </div>
                <div class="company-info">
                    <div class="company-name">Wishluv Buildcon Pvt Ltd</div>
                    <div class="company-address">
                        L-3/9 SK Puri, Opp SK Puri Park,<br>
                        Main Gate, Boring Road Patna. 800001
                    </div>
                </div>
            </div>

            <!-- TITLE BOX -->
            <div class="slip-heading">
                <h1>Salary Slip</h1>
                <span>For the month of <?= $payPeriod ?></span>
            </div>

            <!-- EMPLOYEE DETAILS -->
            <div class="grid-container">
                <div class="col-left">
                    <div class="info-row">
                        <span class="info-label">Employee Name</span>
                        <span class="info-value"><?= htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Employee ID</span>
                        <span class="info-value"><?= htmlspecialchars($data['employee_code']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Designation</span>
                        <span class="info-value"><?= htmlspecialchars($data['designation_title'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Department</span>
                        <span class="info-value"><?= htmlspecialchars($data['dept_name'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <div class="col-right">
                     <div class="info-row">
                        <span class="info-label">Pay Period</span>
                        <span class="info-value"><?= $payPeriod ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payslip No.</span>
                        <span class="info-value">#PAY-<?= $data['year'] ?><?= sprintf('%02d', $data['month']) ?>-<?= $data['id'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Working Days</span>
                        <span class="info-value"><?= $data['total_working_days'] ?> Days</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Effective Paid Days</span>
                        <span class="info-value" style="color:#1e3a8a"><?= $data['present_days'] + $data['holiday_days'] ?> Days</span>
                    </div>
                </div>
            </div>

            <!-- SALARY TABLE -->
            <table class="salary-table">
                <thead>
                    <tr>
                        <th width="35%">Earnings</th>
                        <th width="15%" class="amount-head">Amount (₹)</th>
                        <th width="35%">Deductions</th>
                        <th width="15%" class="amount-head">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic Salary</td>
                        <td class="amount-col"><?= number_format($data['base_salary'], 2) ?></td>
                        <td>Provident Fund (PF)</td>
                        <td class="amount-col"><?= number_format($data['pf_deduction'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>HRA / Allowances</td>
                        <td class="amount-col">0.00</td>
                        <td>ESI</td>
                        <td class="amount-col"><?= number_format($data['esi_deduction'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>Special Allowance</td>
                        <td class="amount-col">0.00</td>
                        <td>Other Deductions</td>
                        <td class="amount-col"><?= number_format($data['other_deductions'], 2) ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 20px;"></td> <!-- Spacer -->
                        <td></td>
                        <td>LOP Days (<?= $data['lop_days'] ?>)</td>
                        <td class="amount-col">-</td>
                    </tr>
                    
                    <!-- TOTAL ROW -->
                    <tr class="total-row">
                        <td>Total Earnings</td>
                        <td class="amount-col"><?= number_format($data['base_salary'], 2) ?></td> <!-- Assuming Base = Gross for now -->
                        <td>Total Deductions</td>
                        <td class="amount-col"><?= number_format($data['pf_deduction'] + $data['esi_deduction'] + $data['other_deductions'], 2) ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- NET PAY CARD -->
            <div class="net-pay-section">
                <div class="words-section">
                    <div class="words-label">Amount in Words</div>
                    <div class="words-value" id="amt-words">Processing...</div>
                </div>
                <div class="net-amount-box">
                    <div class="net-label">Net Pay</div>
                    <div class="net-value">₹<?= number_format($data['net_salary'], 2) ?></div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="slip-footer">
                <p class="footer-note">This is a computer-generated document and does not require a physical signature.</p>
                <div class="company-ref">Wishluv Buildcon Pvt Ltd</div>
            </div>
        </div>
    </div>

    <!-- Floating Print Button -->
    <button class="print-btn" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Print / Save PDF
    </button>

    <script>
        // Number to Words Converter
        function price_in_words(price) {
            var sglDigit = ["Zero", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine"],
                dblDigit = ["Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"],
                tensPlace = ["", "Ten", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"],
                handle_tens = function(dgt, nextDgt) {
                    var out = "";
                    if (dgt == 1) {
                        out += (dblDigit[nextDgt] || "");
                    } else {
                        out += (tensPlace[dgt] || "");
                        if (dgt != 0 && dgt != 1 && nextDgt != 0) {
                            out += " " + (sglDigit[nextDgt] || "");
                        }
                    }
                    return out;
                };

            var str = "",
                digitIdx = 0,
                digit = 0,
                nxtDigit = 0,
                words = [];
            
            price += "";
            var prices = price.split(".");
            var number = prices[0];
            var limit = number.length;
            var threeDigits = false; // to checking hundred place 
            
            for (var i = limit - 1; i >= 0; i--) {
                switch (digitIdx) {
                    case 0:
                        digit = number[i];
                        break;
                    case 1:
                        nxtDigit = number[i];
                        if (!(digit == 0 && nxtDigit == 0)) {
                            words.push(handle_tens(nxtDigit, digit));
                            words.push("");
                        } else {
                            words.push("");
                        }
                        break;
                    case 2:
                        digit = number[i];
                        if (digit != 0) {
                            words.push(sglDigit[digit] + " Hundred");
                        }
                        break;
                    case 3:
                        digit = number[i];
                        if (limit == 4 || (limit > 4 && number[i - 1] == 0)) {
                            words.push(sglDigit[digit] + " Thousand");
                        }
                        break;
                    case 4:
                        nxtDigit = number[i];
                        if (!(digit == 0 && nxtDigit == 0)) {
                             words.push(handle_tens(nxtDigit, digit) + " Thousand");
                        } 
                        break;
                    case 5:
                        digit = number[i];
                        if (limit == 6 || (limit > 6 && number[i - 1] == 0)) {
                            words.push(sglDigit[digit] + " Lakh");
                        } 
                        break;
                    case 6:
                        nxtDigit = number[i];
                         if (!(digit == 0 && nxtDigit == 0)) {
                            words.push(handle_tens(nxtDigit, digit) + " Lakh");
                        }
                        break;
                }
                digitIdx++;
            }
            words = words.reverse();
            return words.join(" ");
        }

        // Run conversion
        const netSalary = <?= $data['net_salary'] ?>;
        const words = price_in_words(Math.floor(netSalary));
        document.getElementById('amt-words').innerText = words ? (words + " Rupees Only") : "Zero Rupees";
    </script>
</body>
</html>