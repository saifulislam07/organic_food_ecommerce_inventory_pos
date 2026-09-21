    <style>
        /*
         | A5, portrait. The sheet is drawn at its true size on screen too, so
         | what you see is what comes out of the printer rather than a wide page
         | that reflows the moment it is printed.
         */
        @page { size: A5 portrait; margin: 10mm 9mm; }

        * { box-sizing: border-box; }

        /* Hind Siliguri carries the Bangla; the Latin faces come first so
           English still sets in the system UI font. sans-serif goes LAST — put
           it earlier and it wins outright, which is what used to happen. */
        body {
            font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, 'Hind Siliguri', sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #16181d;
            margin: 0;
            padding: 16px;
            background: #eceef1;
            -webkit-font-smoothing: antialiased;
        }

        .sheet {
            width: 148mm;
            min-height: 210mm;
            padding: 10mm 9mm;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 8px 24px rgba(0,0,0,.08);
        }

        /* One hairline weight everywhere, so nothing shouts. */
        .rule { border: 0; border-top: 1px solid #d9dce1; margin: 0; }

        /* ------------------------------------------------------------ head */

        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10mm; }
        .brand { min-width: 0; }
        .brand img { height: 13mm; width: auto; max-width: 60mm; display: block; }
        .brand .wordmark { font-size: 15pt; font-weight: 700; letter-spacing: -.01em; margin: 0; }
        .brand .tagline { margin: 3px 0 0; font-size: 7.5pt; color: #6b7280; }

        .doc { text-align: right; white-space: nowrap; }
        .doc .kind { font-size: 13pt; font-weight: 600; letter-spacing: .16em; text-transform: uppercase; margin: 0; }
        .doc dl { margin: 6px 0 0; font-size: 8pt; }
        .doc dt { display: inline; color: #6b7280; }
        .doc dd { display: inline; margin: 0 0 0 4px; font-weight: 600; }
        .doc .line { margin-top: 2px; }

        /* ----------------------------------------------------------- panes */

        .parties { display: flex; gap: 8mm; margin: 5mm 0; }
        .party { flex: 1; min-width: 0; }
        .label {
            font-size: 6.5pt; font-weight: 700; letter-spacing: .14em;
            text-transform: uppercase; color: #8a9099; margin: 0 0 3px;
        }
        .party .who { font-weight: 600; }
        .party p { margin: 0; font-size: 8.5pt; color: #3f434a; word-wrap: break-word; }

        /* ----------------------------------------------------------- items */

        table { width: 100%; border-collapse: collapse; }
        thead th {
            font-size: 6.5pt; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
            color: #8a9099; text-align: left; padding: 0 0 4px;
            border-bottom: 1px solid #16181d;
        }
        tbody td { padding: 5px 0; border-bottom: 1px solid #eceef1; vertical-align: top; font-size: 8.5pt; }
        tbody tr:last-child td { border-bottom: 0; }
        .num { text-align: right; white-space: nowrap; }
        .mid { text-align: center; white-space: nowrap; }
        .item-name { font-weight: 600; }
        .item-variant { display: block; font-size: 7.5pt; color: #8a9099; }

        /* --------------------------------------------------------- totals */

        .totals { display: flex; justify-content: flex-end; margin-top: 4mm; }
        .totals table { width: 62mm; }
        .totals td { padding: 3px 0; font-size: 8.5pt; border: 0; }
        .totals .grand td {
            border-top: 1px solid #16181d; padding-top: 6px;
            font-size: 11pt; font-weight: 700;
        }
        .totals .settle td { color: #6b7280; font-size: 8pt; }
        .totals .settle.first td { padding-top: 6px; }
        .totals .due td { font-weight: 700; color: #16181d; }

        /* ---------------------------------------------------------- words */

        .words { margin-top: 4mm; padding-top: 3mm; border-top: 1px solid #d9dce1; }
        .words p { margin: 0; font-size: 8.5pt; font-weight: 600; }

        .notes { margin-top: 4mm; }
        .notes p { margin: 0; font-size: 8pt; color: #3f434a; }

        .foot { margin-top: 6mm; padding-top: 3mm; border-top: 1px solid #d9dce1; text-align: center; }
        .foot p { margin: 0; font-size: 7.5pt; color: #8a9099; }
        .foot .thanks { color: #3f434a; font-weight: 600; margin-bottom: 2px; }

        /* --------------------------------------------------------- screen */

        .toolbar { max-width: 148mm; margin: 0 auto 14px; display: flex; gap: 8px; justify-content: center; }
        .btn {
            font: inherit; font-size: 9pt; font-weight: 600; padding: 8px 18px; border-radius: 6px;
            border: 1px solid transparent; cursor: pointer; text-decoration: none; display: inline-flex;
            align-items: center; gap: 6px;
        }
        .btn-print { background: #16181d; color: #fff; }
        .btn-back { background: #fff; color: #3f434a; border-color: #d9dce1; }

        /* A true-size A5 preview is wider than a phone. Let the sheet shrink on
           screen rather than push the page sideways — printing is unaffected,
           since the print rules below re-fix it to the paper. */
        @media screen and (max-width: 170mm) {
            body { padding: 10px; }
            .sheet { width: 100%; min-height: 0; padding: 6mm; }
            .toolbar { max-width: 100%; }
        }

        @media print {
            body { background: #fff; padding: 0; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .toolbar { display: none; }

            /* The logo is the one piece of colour on the page; browsers drop
               images' colour in print by default. */
            .brand img { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            /* An order long enough to run onto a second sheet keeps its column
               headings, never splits a line across the fold, and never leaves
               the totals stranded on their own page. */
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            .totals, .words, .foot { page-break-inside: avoid; }
        }
    </style>
