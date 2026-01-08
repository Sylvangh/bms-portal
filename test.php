  win.document.write(`
    <!DOCTYPE html>
<html>
<head>
  <title>Barangay Clearance</title>

  <style>
    @page {
      size: legal portrait;
      margin: 25mm 20mm;
    }

    body {
      font-family: "Times New Roman", serif;
      line-height: 1.6;
      position: relative;
    }

    /* HEADER */
    .header {
      position: relative;
      text-align: center;
      margin-bottom: 30px;
    }

    .logo-left {
      position: absolute;
      top: 0;
      left: 0;
      width: 90px;
    }

    .logo-right {
      position: absolute;
      top: 0;
      right: 0;
      width: 90px;
    }

    .header p {
      margin: 2px 0;
    }

    h2, h3 {
      margin: 10px 0;
    }

    /* WATERMARK */
    .watermark {
      position: fixed;
      top: 50%;
      left: 50%;
      width: 420px;
      opacity: 0.06;
      transform: translate(-50%, -50%);
      z-index: -1;
    }

    .content {
      margin-top: 40px;
      text-align: justify;
      font-size: 16px;
    }

    .signature {
      margin-top: 70px;
      text-align: right;
    }

    @media print {
      body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>

<body>

<!-- WATERMARK -->
<img src="logo.png" class="watermark">

<div class="header">
  <img src="logo.png" class="logo-left">
  <img src="Bagong-Pilipinas-Logo.png" class="logo-right">

  <p><b>Republic of the Philippines</b></p>
  <p>Province of Sorsogon</p>
  <p>Municipality of Sta. Magdalena</p>
  <p>Barangay Peñafrancia</p>

  <br>
  <h3>OFFICE OF THE BARANGAY CHAIRMAN</h3>
  <h2>BARANGAY CLEARANCE</h2>
</div>

<div class="content">
  <p>
    This is to certify that <b> ${fullName}</b> is a Filipino of legal age
    and a resident of <b>Barangay Peñafrancia, Sta. Magdalena, Sorsogon</b>.
  </p>

  <p>
    This is to further certify that he/she is a person of good moral character,
    a law-abiding citizen, and has never been convicted of any crime nor been
    a member of any subversive organization that seeks to overthrow our government.
  </p>

  <p>
    Issued this <b>${today}</b> upon request of the above-named for whatever
    legal purpose it may serve.
  </p>
</div>

<div class="signature">
  <p><b>____________________________</b></p>
  <p>Name of Barangay Chairman</p>
  <p>Barangay Chairman</p>

  <br>

  <p>Date of Issuance: ${today}</p>
  <p>Place of Issuance: Barangay Peñafrancia</p>
</div>
    <button onclick="window.print()">Print</button>
  `);