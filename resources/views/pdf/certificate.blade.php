<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sertifikat - {{ $certificate_number }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: #fff;
        }

        .certificate-container {
            width: 100%;
            height: 100%;
            padding: 40px;
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .certificate-inner {
            background: #fff;
            border-radius: 12px;
            padding: 50px;
            height: 100%;
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .border-accent {
            position: absolute;
            top: 15px;
            left: 15px;
            right: 15px;
            bottom: 15px;
            border: 3px solid #667eea;
            border-radius: 8px;
            pointer-events: none;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-area {
            margin-bottom: 20px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 2px;
        }

        .certificate-title {
            font-size: 42px;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 10px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .certificate-subtitle {
            font-size: 16px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .content {
            text-align: center;
            margin-bottom: 30px;
        }

        .presented-to {
            font-size: 14px;
            color: #718096;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .recipient-name {
            font-size: 38px;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
            display: inline-block;
        }

        .completion-text {
            font-size: 16px;
            color: #4a5568;
            line-height: 1.8;
            max-width: 600px;
            margin: 0 auto;
        }

        .course-title {
            font-size: 22px;
            font-weight: bold;
            color: #667eea;
            margin: 15px 0;
        }

        .footer {
            display: table;
            width: 100%;
            margin-top: 40px;
        }

        .footer-left, .footer-center, .footer-right {
            display: table-cell;
            vertical-align: top;
            width: 33.33%;
        }

        .footer-left {
            text-align: left;
        }

        .footer-center {
            text-align: center;
        }

        .footer-right {
            text-align: right;
        }

        .signature-area {
            text-align: center;
        }

        .signature-line {
            width: 180px;
            border-bottom: 2px solid #1a202c;
            margin: 0 auto 10px;
            padding-top: 40px;
        }

        .signature-name {
            font-size: 14px;
            font-weight: bold;
            color: #1a202c;
        }

        .signature-title {
            font-size: 12px;
            color: #718096;
        }

        .meta-info {
            font-size: 11px;
            color: #718096;
            line-height: 1.6;
        }

        .certificate-number {
            font-weight: bold;
            color: #4a5568;
        }

        .verification-box {
            background: #f7fafc;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
        }

        .verification-code {
            font-family: monospace;
            font-size: 12px;
            color: #667eea;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .issued-date {
            margin-top: 10px;
            font-size: 12px;
            color: #718096;
        }

        .grade-badge {
            display: inline-block;
            background: #667eea;
            color: #fff;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-inner">
            <div class="border-accent"></div>

            <div class="header">
                <div class="logo-area">
                    <div class="logo-text">EnterLMS</div>
                </div>
                <div class="certificate-title">Sertifikat</div>
                <div class="certificate-subtitle">{{ $subtitle }}</div>
            </div>

            <div class="content">
                <div class="presented-to">Diberikan kepada</div>
                <div class="recipient-name">{{ $recipient_name }}</div>

                <div class="completion-text">
                    {{ $completion_text }}
                </div>

                <div class="course-title">"{{ $course_title }}"</div>

                @if($grade)
                    <div class="grade-badge">
                        Nilai: {{ number_format($grade, 1) }}
                    </div>
                @endif
            </div>

            <div class="footer">
                <div class="footer-left">
                    <div class="meta-info">
                        <div>Nomor Sertifikat:</div>
                        <div class="certificate-number">{{ $certificate_number }}</div>

                        <div class="verification-box">
                            <div>Kode Verifikasi:</div>
                            <div class="verification-code">{{ $verification_code }}</div>
                        </div>
                    </div>
                </div>

                <div class="footer-center">
                    <div class="signature-area">
                        <div class="signature-line"></div>
                        <div class="signature-name">Administrator LMS</div>
                        <div class="signature-title">EnterLMS Learning Platform</div>
                    </div>
                </div>

                <div class="footer-right">
                    <div class="meta-info">
                        <div>Tanggal Diterbitkan:</div>
                        <div class="certificate-number">{{ $issued_at }}</div>

                        <div class="issued-date">
                            Verifikasi sertifikat ini di:<br>
                            {{ $verification_url }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
