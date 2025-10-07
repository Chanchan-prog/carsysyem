<?php
/**
 * Simple PDF Generator for Receipts
 * This is a basic implementation. For production use, consider using libraries like TCPDF, FPDF, or wkhtmltopdf
 */

/**
 * Generate PDF from HTML content
 */
function generate_pdf_from_html($html_content, $filename) {
    // Create receipts directory if it doesn't exist
    $receipts_dir = __DIR__ . '/../uploads/receipts/';
    if (!is_dir($receipts_dir)) {
        mkdir($receipts_dir, 0755, true);
    }
    
    $file_path = $receipts_dir . $filename;
    
    // For now, we'll save as HTML and let the browser handle PDF conversion
    // In a production environment, you would use a proper PDF library
    file_put_contents($file_path, $html_content);
    
    return $file_path;
}

/**
 * Generate receipt PDF with proper styling
 */
function generate_receipt_pdf_advanced($payment_data, $receipt_number) {
    $company_name = get_system_setting('company_name', 'Car Loan Management System');
    $current_date = date('F d, Y');
    $current_time = date('h:i A');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Payment Receipt - ' . $receipt_number . '</title>
        <style>
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
            
            body { 
                font-family: "Times New Roman", serif; 
                margin: 0; 
                padding: 20px; 
                background: white;
                color: #000;
            }
            
            .receipt { 
                max-width: 800px; 
                margin: 0 auto; 
                border: 3px solid #000; 
                padding: 30px; 
                background: white;
            }
            
            .header { 
                text-align: center; 
                border-bottom: 3px solid #000; 
                padding-bottom: 20px; 
                margin-bottom: 30px; 
            }
            
            .company-name { 
                font-size: 28px; 
                font-weight: bold; 
                color: #000; 
                margin-bottom: 10px;
                text-transform: uppercase;
                letter-spacing: 2px;
            }
            
            .receipt-title { 
                font-size: 20px; 
                color: #333; 
                font-weight: bold;
                margin-top: 10px;
            }
            
            .receipt-info { 
                margin-bottom: 25px; 
            }
            
            .receipt-info table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 20px;
            }
            
            .receipt-info td { 
                padding: 12px 8px; 
                border-bottom: 1px solid #ddd; 
                font-size: 14px;
            }
            
            .receipt-info td:first-child { 
                font-weight: bold; 
                width: 40%; 
                background: #f8f9fa;
            }
            
            .payment-details { 
                margin-bottom: 25px; 
            }
            
            .payment-details h3 { 
                color: #000; 
                border-bottom: 2px solid #000; 
                padding-bottom: 8px; 
                margin-bottom: 15px;
                font-size: 16px;
                text-transform: uppercase;
            }
            
            .amount { 
                font-size: 32px; 
                font-weight: bold; 
                color: #000; 
                text-align: center; 
                margin: 30px 0; 
                padding: 20px;
                border: 3px solid #000;
                background: #f8f9fa;
            }
            
            .footer { 
                text-align: center; 
                margin-top: 40px; 
                padding-top: 20px; 
                border-top: 2px solid #000; 
                color: #333;
                font-size: 12px;
            }
            
            .print-button { 
                text-align: center; 
                margin: 20px 0; 
            }
            
            .print-button button { 
                background: #007bff; 
                color: white; 
                padding: 12px 24px; 
                border: none; 
                border-radius: 5px; 
                cursor: pointer; 
                font-size: 16px;
                margin: 5px;
            }
            
            .print-button button:hover {
                background: #0056b3;
            }
            
            .signature-section {
                margin-top: 40px;
                display: flex;
                justify-content: space-between;
            }
            
            .signature-box {
                width: 200px;
                text-align: center;
                border-top: 1px solid #000;
                padding-top: 10px;
                margin-top: 50px;
            }
            
            .receipt-number {
                font-size: 18px;
                font-weight: bold;
                color: #007bff;
                background: #e7f3ff;
                padding: 10px;
                border-radius: 5px;
                text-align: center;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="receipt">
            <div class="header">
                <div class="company-name">' . htmlspecialchars($company_name) . '</div>
                <div class="receipt-title">PAYMENT RECEIPT</div>
                <div class="receipt-number">Receipt #' . htmlspecialchars($receipt_number) . '</div>
            </div>
            
            <div class="receipt-info">
                <table>
                    <tr>
                        <td>Receipt Number:</td>
                        <td><strong>' . htmlspecialchars($receipt_number) . '</strong></td>
                    </tr>
                    <tr>
                        <td>Payment Date:</td>
                        <td>' . date('F d, Y', strtotime($payment_data['created_at'])) . '</td>
                    </tr>
                    <tr>
                        <td>Payment Time:</td>
                        <td>' . date('h:i A', strtotime($payment_data['created_at'])) . '</td>
                    </tr>
                    <tr>
                        <td>Payment Method:</td>
                        <td>' . ucfirst(htmlspecialchars($payment_data['method'])) . '</td>
                    </tr>
                </table>
            </div>
            
            <div class="payment-details">
                <h3>Customer Information</h3>
                <table>
                    <tr>
                        <td>Customer Name:</td>
                        <td><strong>' . htmlspecialchars($payment_data['full_name']) . '</strong></td>
                    </tr>
                    <tr>
                        <td>Username:</td>
                        <td>' . htmlspecialchars($payment_data['username']) . '</td>
                    </tr>
                </table>
            </div>
            
            <div class="payment-details">
                <h3>Vehicle Information</h3>
                <table>
                    <tr>
                        <td>Vehicle Model:</td>
                        <td><strong>' . htmlspecialchars($payment_data['model']) . '</strong></td>
                    </tr>
                    <tr>
                        <td>Plate Number:</td>
                        <td><strong>' . htmlspecialchars($payment_data['plate_no']) . '</strong></td>
                    </tr>
                </table>
            </div>
            
            <div class="payment-details">
                <h3>Payment Details</h3>
                <table>
                    <tr>
                        <td>Installment Number:</td>
                        <td><strong>#' . $payment_data['installment_no'] . '</strong></td>
                    </tr>
                    <tr>
                        <td>Due Date:</td>
                        <td>' . date('F d, Y', strtotime($payment_data['due_date'])) . '</td>
                    </tr>
                    <tr>
                        <td>EMI Amount:</td>
                        <td>₱' . number_format($payment_data['emi_amount'], 2) . '</td>
                    </tr>
                    <tr>
                        <td>Amount Paid:</td>
                        <td><strong>₱' . number_format($payment_data['amount'], 2) . '</strong></td>
                    </tr>
                </table>
            </div>
            
            <div class="amount">
                AMOUNT PAID: ₱' . number_format($payment_data['amount'], 2) . '
            </div>
            
            <div class="signature-section">
                <div class="signature-box">
                    <div>Customer Signature</div>
                </div>
                <div class="signature-box">
                    <div>Authorized Signature</div>
                </div>
            </div>
            
            <div class="footer">
                <p><strong>Thank you for your payment!</strong></p>
                <p>This is a computer-generated receipt.</p>
                <p>Generated on: ' . $current_date . ' at ' . $current_time . '</p>
                <p>For any queries, please contact our customer service.</p>
            </div>
        </div>
        
        <div class="print-button no-print">
            <button onclick="window.print()">🖨️ Print Receipt</button>
            <button onclick="window.close()">❌ Close</button>
        </div>
        
        <script>
            // Auto-print when opened in new window
            if (window.opener) {
                setTimeout(function() {
                    window.print();
                }, 1000);
            }
        </script>
    </body>
    </html>';
    
    return $html;
}
?>
