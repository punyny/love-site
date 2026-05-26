<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Phnom_Penh');

function toPdfSafeAscii(string $text): string
{
    $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($converted === false) {
        $converted = $text;
    }

    return (string) preg_replace('/[^\x20-\x7E]/', '?', $converted);
}

function escapePdfText(string $text): string
{
    return str_replace(
        ['\\', '(', ')'],
        ['\\\\', '\\(', '\\)'],
        $text
    );
}

function buildSimplePdf(array $lines): string
{
    $content = "BT\n/F1 12 Tf\n50 790 Td\n";
    $isFirstLine = true;

    foreach ($lines as $line) {
        if (!$isFirstLine) {
            $content .= "0 -18 Td\n";
        }
        $safeLine = escapePdfText(toPdfSafeAscii((string) $line));
        $content .= "({$safeLine}) Tj\n";
        $isFirstLine = false;
    }

    $content .= "ET";

    $objects = [
        1 => "<< /Type /Catalog /Pages 2 0 R >>",
        2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
        3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
        4 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
        5 => "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream",
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    for ($i = 1; $i <= 5; $i++) {
        $offsets[$i] = strlen($pdf);
        $pdf .= "{$i} 0 obj\n{$objects[$i]}\nendobj\n";
    }

    $xrefPosition = strlen($pdf);
    $pdf .= "xref\n0 6\n0000000000 65535 f \n";

    for ($i = 1; $i <= 5; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }

    $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefPosition}\n%%EOF";

    return $pdf;
}

$recipientEmail = 'matt.rorny2023@diu.edu.kh';
$decisionId = '';
$letter = '';
$createdAt = '';
$mailStatus = '';
$mailtoUrl = '';
$pdfBase64 = '';
$pdfFilename = '';
$autoOpenDraft = false;
$showLetter = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['decision'] ?? '') === 'yes') {
    $showLetter = true;
    $now = new DateTimeImmutable('now');
    $decisionId = 'LUV-' . $now->format('Ymd-His');
    $createdAt = $now->format('d/m/Y H:i:s');

    $letter = "លិខិតបញ្ជាក់សេចក្តីស្រឡាញ់ជាផ្លូវការ\n"
        . "Official Declaration of Love\n\n"
        . "លេខកូដបេះដូង / Heart Ref No.: {$decisionId}\n"
        . "កាលបរិច្ឆេទ / Date: {$createdAt}\n"
        . "ជូនចំពោះម្ចាស់បេះដូង / To the owner of my heart: {$recipientEmail}\n"
        . "ប្រធានបទ / Subject: លិខិតបញ្ជាក់អំពីការសម្រេចចិត្តនៃបេះដូង (Confirmation of the Heart's Decision)\n\n"
        . "តាមរយៈលិខិតដ៏ពិសេសមួយនេះ ខ្ញុំសូមប្រកាស និងបញ្ជាក់ជាផ្លូវការចេញពីក្រអៅបេះដូងថា៖\n\n"
        . "រាល់ការឆ្លើយតបចំពោះសំណួរ \"If I want ឲអ្នកផ្សេងមើលបាន\" គឺត្រូវបានសម្រេចជាឯកច្ឆន្ទដោយគ្មានការស្ទាក់ស្ទើរថា៖\n\n"
        . "\"YES, I DO LOVE YOU!\" (ស្រឡាញ់... ស្រឡាញ់ខ្លាំងណាស់!)។\n\n"
        . "ការសម្រេចចិត្តមួយនេះ មិនមែនជារឿងចៃដន្យឡើយ តែវាជាចម្លើយដែលកើតចេញពីក្តីស្រឡាញ់ដ៏ស្មោះត្រង់ ការយកចិត្តទុកដាក់ និងមនោសញ្ចេតនាដែលមិនអាចប្រែប្រួលបាន។\n\n"
        . "លិខិតនេះត្រូវបានរៀបចំឡើងជា \"ទម្រង់ស្តង់ដារនៃសេចក្តីស្នេហា\" ដើម្បីប្រើប្រាស់ជាឯកសារតម្កល់ទុកក្នុងបេះដូងរបស់អ្នក និងជាការធានាអះអាងថា ក្តីស្រឡាញ់មួយនេះនឹងនៅតែស្ថិតស្ថេរជារៀងរហូត។";

    $subject = "Official Declaration of Love - {$decisionId}";
    $pdfFilename = "official-love-declaration-{$decisionId}.pdf";
    $pdfLines = [
        'Official Declaration of Love',
        "Heart Ref No.: {$decisionId}",
        "Date: {$createdAt}",
        "To the owner of my heart: {$recipientEmail}",
        '',
        'Decision: "YES, I DO LOVE YOU!"',
        '',
        'This is a formal and heartfelt declaration of love.',
        '',
        'Signature: ________________________',
    ];
    $pdfBinary = buildSimplePdf($pdfLines);
    $pdfBase64 = base64_encode($pdfBinary);

    $boundary = 'mix_' . bin2hex(random_bytes(12));
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'From: no-reply@localhost',
        "Content-Type: multipart/mixed; boundary=\"{$boundary}\"",
    ]);

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $letter . "\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: application/pdf; name=\"{$pdfFilename}\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"{$pdfFilename}\"\r\n\r\n";
    $body .= chunk_split($pdfBase64) . "\r\n";
    $body .= "--{$boundary}--";

    $mailSent = @mail($recipientEmail, $subject, $body, $headers);
    if ($mailSent) {
        $mailStatus = "PDF attached email sent to {$recipientEmail}.";
        $autoOpenDraft = false;
    } else {
        $mailStatus = "Server auto-send failed. Opening email draft now. Please attach the downloaded PDF manually.";
        $autoOpenDraft = true;
    }

    $mailtoUrl = 'mailto:' . rawurlencode($recipientEmail)
        . '?subject=' . rawurlencode($subject)
        . '&body=' . rawurlencode($letter . "\n\nPlease attach PDF file: {$pdfFilename}");
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>A Question For You...</title>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700&family=Nunito:wght@500;700&display=swap");

      :root {
        --bg-one: #ffc8db;
        --bg-two: #ffe8b6;
        --ink: #4f2f43;
        --line: #ffbfd6;
        --card: #fffaf3;
        --letter-bg: #fff8fc;
        --btn-one: #ff739c;
        --btn-two: #ff4f83;
      }

      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 16px;
        background:
          radial-gradient(circle at 12% 16%, #fff7d6 0, #fff7d6 14%, transparent 15%),
          radial-gradient(circle at 88% 20%, #ffd8ea 0, #ffd8ea 12%, transparent 13%),
          linear-gradient(135deg, var(--bg-one), var(--bg-two));
        font-family: "Baloo 2", "Nunito", "Segoe UI", sans-serif;
        overflow: hidden;
        position: relative;
      }

      body::before,
      body::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        opacity: 0.45;
        pointer-events: none;
        animation: bob 8s ease-in-out infinite;
      }

      body::before {
        width: 180px;
        height: 180px;
        background: #ffd3e4;
        left: -40px;
        top: 18%;
      }

      body::after {
        width: 210px;
        height: 210px;
        background: #ffe6a8;
        right: -70px;
        bottom: 8%;
        animation-delay: 1.4s;
      }

      #preface,
      #main,
      #letterSection {
        position: relative;
        z-index: 1;
      }

      #preface {
        text-align: center;
        animation: fadeIn 0.9s ease;
        display: <?php echo $showLetter ? 'none' : 'block'; ?>;
        background: rgba(255, 255, 255, 0.45);
        border: 2px dashed var(--line);
        border-radius: 28px;
        padding: 28px 22px;
        box-shadow: 0 16px 34px rgba(130, 56, 88, 0.15);
      }

      #preface h1 {
        color: var(--ink);
        margin: 0 0 18px;
        font-size: clamp(24px, 4.8vw, 38px);
        line-height: 1.2;
      }

      .preface-btn {
        padding: 11px 24px;
        font-size: 20px;
        background: linear-gradient(135deg, var(--btn-one), var(--btn-two));
        color: #fff;
        border: none;
        border-radius: 999px;
        cursor: pointer;
        font-weight: 700;
        box-shadow: 0 10px 20px rgba(255, 79, 131, 0.28);
        transition: transform 0.2s, box-shadow 0.2s;
      }

      .preface-btn:hover {
        transform: translateY(-2px) scale(1.03);
        box-shadow: 0 13px 24px rgba(255, 79, 131, 0.35);
      }

      #main {
        text-align: center;
        display: none;
      }

      #main h1 {
        margin-bottom: 30px;
        color: var(--ink);
        font-size: clamp(32px, 7vw, 54px);
      }

      #letterSection {
        display: <?php echo $showLetter ? 'block' : 'none'; ?>;
        width: min(94vw, 780px);
        max-height: 88vh;
        overflow-y: auto;
        background: linear-gradient(180deg, var(--card), #fff3fa 40%, #fff8ec);
        border: 2px dashed var(--line);
        border-radius: 26px;
        box-shadow: 0 18px 42px rgba(136, 64, 95, 0.2);
        padding: 26px;
        color: var(--ink);
        animation: popIn 0.42s ease both;
      }

      #letterSection::before {
        content: "official love form";
        display: inline-block;
        padding: 5px 12px;
        border-radius: 999px;
        background: #ffe0ec;
        color: #b84f78;
        font-size: 12px;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 10px;
      }

      #letterSection h2 {
        margin: 0 0 10px;
        font-size: clamp(24px, 4vw, 34px);
        line-height: 1.25;
      }

      #letterMeta {
        margin: 0 0 14px;
        color: #814d67;
        font-size: 14px;
        font-weight: 700;
        background: #fff3f9;
        border: 1px solid #ffd0e2;
        border-radius: 12px;
        display: inline-block;
        padding: 6px 10px;
      }

      #letterText {
        white-space: pre-wrap;
        line-height: 1.82;
        font-family: "Nunito", "Baloo 2", sans-serif;
        border: 2px solid #ffd8e8;
        border-radius: 18px;
        background:
          repeating-linear-gradient(
            to bottom,
            var(--letter-bg) 0,
            var(--letter-bg) 29px,
            #ffeaf3 30px
          );
        padding: 20px;
      }

      #emailStatus {
        margin-top: 12px;
        color: #5c4460;
        font-size: 14px;
        background: #fff0f7;
        border-radius: 10px;
        padding: 9px 12px;
      }

      .letter-actions {
        display: flex;
        gap: 12px;
        margin-top: 16px;
        flex-wrap: wrap;
      }

      .letter-btn {
        padding: 11px 18px;
        border: none;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--btn-one), var(--btn-two));
        color: #fff;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 18px rgba(255, 79, 131, 0.28);
        transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
      }

      .letter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 11px 22px rgba(255, 79, 131, 0.34);
        filter: brightness(1.05);
      }

      .letter-btn.secondary {
        background: linear-gradient(135deg, #5dc7b5, #2fa595);
        box-shadow: 0 8px 18px rgba(47, 165, 149, 0.27);
      }

      .buttons {
        margin-right: 145px;
        position: relative;
        display: inline-block;
      }

      .action-btn {
        width: 110px;
        padding: 7px 0;
        font-size: 24px;
        border: none;
        border-radius: 16px;
        letter-spacing: 1px;
        cursor: pointer;
        font-family: "Baloo 2", "Nunito", sans-serif;
        font-weight: 700;
      }

      #yesBtn {
        background: linear-gradient(135deg, #2ec179, #26a767);
        color: #fff;
        box-shadow: 0 8px 16px rgba(38, 167, 103, 0.27);
      }

      #noBtn {
        background: linear-gradient(135deg, #ff6a89, #ee426d);
        color: #fff;
        position: absolute;
        left: 120px;
        top: 0;
      }

      @media (max-width: 680px) {
        body {
          padding: 12px;
        }

        #preface {
          padding: 22px 16px;
          border-radius: 22px;
        }

        .buttons {
          margin-right: 130px;
        }

        .action-btn {
          width: 104px;
          font-size: 22px;
        }

        #letterSection {
          width: 96vw;
          padding: 20px 16px;
          border-radius: 22px;
        }

        #letterText {
          padding: 16px;
          font-size: 14px;
          line-height: 1.72;
        }
      }

      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
      }

      @keyframes popIn {
        from { opacity: 0; transform: scale(0.98) translateY(8px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
      }

      @keyframes bob {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
      }
    </style>
  </head>
  <body>
    <div id="preface">
      <h1>I have an important question for you... 💌</h1>
      <button class="preface-btn" type="button" onclick="showQuestion()">What is it?</button>
    </div>

    <div id="main">
      <h1>If I want ឲអ្នកផ្សេងមើលបាន ❤️</h1>
      <div class="buttons">
        <form method="post" style="display: inline;">
          <input type="hidden" name="decision" value="yes" />
          <button id="yesBtn" class="action-btn" type="submit">yes😍</button>
        </form>
        <button id="noBtn" class="action-btn" type="button">no😢</button>
      </div>
    </div>

    <div id="letterSection">
      <h2>លិខិតបញ្ជាក់សេចក្តីស្រឡាញ់ត្រូវបានបង្កើត</h2>
      <p id="letterMeta">
        <?php if ($showLetter): ?>
          <?php echo htmlspecialchars("Heart Ref: {$decisionId} | Created: {$createdAt}", ENT_QUOTES, 'UTF-8'); ?>
        <?php endif; ?>
      </p>
      <div id="letterText"><?php echo nl2br(htmlspecialchars($letter, ENT_QUOTES, 'UTF-8')); ?></div>
      <p id="emailStatus"><?php echo htmlspecialchars($mailStatus, ENT_QUOTES, 'UTF-8'); ?></p>
      <div class="letter-actions">
        <?php if ($showLetter): ?>
          <a class="letter-btn" href="<?php echo htmlspecialchars($mailtoUrl, ENT_QUOTES, 'UTF-8'); ?>">Open Email Draft</a>
          <a
            class="letter-btn secondary"
            download="<?php echo htmlspecialchars($pdfFilename, ENT_QUOTES, 'UTF-8'); ?>"
            href="data:application/pdf;base64,<?php echo htmlspecialchars($pdfBase64, ENT_QUOTES, 'UTF-8'); ?>"
          >Download PDF</a>
        <?php endif; ?>
        <button class="letter-btn secondary" type="button" onclick="copyLetter()">Copy Letter</button>
      </div>
    </div>

    <script>
      const letterContent = <?php echo json_encode($letter, JSON_UNESCAPED_UNICODE); ?>;
      const shouldAutoOpenEmail = <?php echo $autoOpenDraft ? 'true' : 'false'; ?>;
      const mailtoUrl = <?php echo json_encode($mailtoUrl, JSON_UNESCAPED_UNICODE); ?>;

      function showQuestion() {
        const preface = document.getElementById("preface");
        const main = document.getElementById("main");
        const letterSection = document.getElementById("letterSection");
        preface.style.display = "none";
        main.style.display = "block";
        letterSection.style.display = "none";
      }

      const noBtn = document.getElementById("noBtn");
      let isFixed = false;

      noBtn.addEventListener("mouseenter", moveButton);
      noBtn.addEventListener("touchstart", moveButton, { passive: false });

      function moveButton(e) {
        if (e.type === "touchstart") {
          e.preventDefault();
        }

        const padding = 20;
        const maxX = window.innerWidth - noBtn.offsetWidth - padding;
        const maxY = window.innerHeight - noBtn.offsetHeight - padding;
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        let newX, newY;
        while (true) {
          newX = Math.random() * maxX;
          newY = Math.random() * maxY;
          if ((newX - clientX) ** 2 + (newY - clientY) ** 2 >= 150 * 150) {
            break;
          }
        }

        if (!isFixed) {
          const rect = noBtn.getBoundingClientRect();
          noBtn.style.position = "fixed";
          noBtn.style.left = rect.left + "px";
          noBtn.style.top = rect.top + "px";
          noBtn.offsetHeight;
          noBtn.style.transition = "left 0.3s ease, top 0.3s ease";
          isFixed = true;
        }

        noBtn.style.left = newX + "px";
        noBtn.style.top = newY + "px";
      }

      function copyLetter() {
        if (!letterContent) {
          return;
        }

        navigator.clipboard.writeText(letterContent)
          .then(() => {
            document.getElementById("emailStatus").textContent = "Letter copied to clipboard.";
          })
          .catch(() => {
            document.getElementById("emailStatus").textContent =
              "Copy failed on this browser. Please copy manually from the letter text.";
          });
      }

      if (shouldAutoOpenEmail && mailtoUrl) {
        setTimeout(() => {
          window.location.href = mailtoUrl;
        }, 400);
      }
    </script>
  </body>
</html>
