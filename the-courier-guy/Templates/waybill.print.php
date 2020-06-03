<?php
global $TCG_Plugin;
$pluginPath = $TCG_Plugin->getPluginPath();
$orderDate = new DateTime($order->order_date);
$orderDateForDisplay = $orderDate->format('d-m-Y');
$service = $collectionParams['details']['service'];
$parcels = $collectionParams['contents'];
$numberOfCopies = 4;
if(!empty($shippingSettings['order_waybill_pdf_copy_quantity'])){
    $numberOfCopies = $shippingSettings['order_waybill_pdf_copy_quantity'];
}
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>Waybill - <?= $waybillNumber; ?></title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <meta name="robots" content="all,index,follow"/>
    <style>
        body {
            color: black;
            font-size: 7px;
            line-height: 7px;
            font-family: sans-serif;
        }

        table {
            border-collapse: collapse;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        table.blue td {
            border: 1px solid #32378b;
        }

        table.blue td table td {
            border: none;
        }

        table.red td {
            border: 1px solid #e92a18;
        }

        table.red td table td {
            border: none;
        }

        td {
            vertical-align: middle;
            padding: 2px;
        }

        td.red, td.blue {
            color: #e92a18;
            font-weight: bold;
        }

        td.blue {
            color: #32378b;
        }

        td.black {
            font-family: serif;
            font-size: 8px;
            line-height: 8px;
        }

        td.border {
            border: 1px solid black;
        }

        td.bluebg {
            color: white;
            background: #32378b;
            font-weight: bold;
        }

        small {
            font-size: 6px;
        }

        td.line {
            border-bottom: 1px solid #e92a18 !important;
        }

        h1 {
            color: #32378b;
            margin: 0;
            font-size: 11px;
            line-height: 13px;
            text-align: center;
        }

        h3 {
            color: #32378b;
            margin: 0;
            font-size: 8px;
            line-height: 10px;
            text-align: center;
        }

        p {
            font-weight: bold;
            line-height: 125%;
            color: #32378b;
        }

        p strong {
            color: #e92a18;
            margin: 0;
            padding: 0;
        }

        .page_break {
            page-break-before: always;
        }
    </style>
</head>
<body>
<?php
for ($i = 0; $i < $numberOfCopies; ++$i) {
    ?>
    <table class="blue" style="margin-bottom:5px;">
        <tr>
            <td colspan="7" style="vertical-align: top;">
                <table>
                    <tr>
                        <td style="width:120px;">
                            <img src="<?= $pluginPath; ?>dist/images/logo.png" alt="logo" width="120">
                        </td>
                        <td>
                            <h1>Worldwide Express </h1>
                            <h3>We would love to handle your package</h3>
                        </td>
                        <td>
                            <table>
                                <tr>
                                    <td style="text-align:center">
                                        <p><strong>HEAD OFFICE:</strong><br>
                                            P O Box 532<br>
                                            Lanseria<br>
                                            1748</p>
                                    </td>
                                    <td style="text-align:center">
                                        <p><strong>Sharecall No.:</strong><br>
                                            0861 203 203<br>
                                            <strong>Fax:</strong>
                                            0861 114 273<br>
                                            <strong>After Hours:</strong><br>
                                            0861 114 273</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            <td colspan="2">
                <table style="width: 100%;">
                    <tr>
                        <td><img width="200px" src="<?= $barcodePath; ?>"/></td>
                    </tr>
                    <tr>
                        <td style="text-align: center;"><strong><?= $waybillNumber; ?></strong></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="width: 15%;">
                <table>
                    <tr>
                        <td class="red">ACCOUNT No.</td>
                    </tr>
                    <tr>
                        <td class="black"><?= $collectionParams['details']['accnum']; ?></td>
                    </tr>
                </table>
            </td>
            <td style="width: 15%;">
                <table>
                    <tr>
                        <td class="red">CLIENT REFERENCE</td>
                    </tr>
                    <tr>
                        <td class="black"><?= $collectionParams['details']['accnum']; ?></td>
                    </tr>
                </table>
            </td>
            <td style="width: 12%;">
                <table>
                    <tr>
                        <td class="red">DATE
                            <small>(DD-MM-YY)</small>
                        </td>
                    </tr>
                    <tr>
                        <td class="black"><?= $orderDateForDisplay; ?></td>
                    </tr>
                </table>
            </td>
            <td style="width: 7%;">
                <table>
                    <tr>
                        <td class="red">PARCELS</td>
                    </tr>
                    <tr>
                        <td class="black"><?= count($parcels); ?></td>
                    </tr>
                </table>
            </td>
            <?php
            $mass = 0;
            $width = 0;
            $height = 0;
            $length = 0;
            foreach ($parcels as $parcel) {
                $mass = $mass + (int)$parcel['actmass'];
                $width = $width + (int)$parcel['dim1'];
                $height = $height + (int)$parcel['dim2'];
                $length = $length + (int)$parcel['dim3'];
            }
            ?>
            <td style="width: 7%;">
                <table>
                    <tr>
                        <td class="red">MASS</td>
                    </tr>
                    <tr>
                        <td class="black"><?= $mass; ?></td>
                    </tr>
                </table>
            </td>
            <td style="width: 7%;">
                <table>
                    <tr>
                        <td class="red">VOLUME</td>
                    </tr>
                    <tr>
                        <td class="black"><?= ($width * $height * $length); ?></td>
                    </tr>
                </table>
            </td>
            <td style="width: 7%;">
                <table>
                    <tr>
                        <td class="red">ORIGIN</td>
                    </tr>
                    <tr>
                        <td class="black"><?= $collectionParams['details']['origperadd3']; ?></td>
                    </tr>
                </table>
            </td>
            <td style="width: 7%;">
                <table>
                    <tr>
                        <td class="red">DEST.</td>
                    </tr>
                    <tr>
                        <td class="black"><?= $collectionParams['details']['destperadd3']; ?></td>
                    </tr>
                </table>
            </td>
            <td>
                <table>
                    <tr>
                        <td class="red">OFFICE REFERENCE</td>
                    </tr>
                    <tr>
                        <td class="black">N/A</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table style="margin-bottom:5px;">
        <tr>
            <td style="width: 50%; padding:0 3px 0 0; vertical-align: top;">
                <table class="red">
                    <tr>
                        <td style="width: 50%;">
                            <table>
                                <tr>
                                    <td class="blue">Contact Name:</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['destpercontact']; ?></td>
                                </tr>
                            </table>
                        </td>
                        <td colspan="2" style="width: 50%;">
                            <table>
                                <tr>
                                    <td class="blue">Contact Phone Number (Very Important):</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['origperphone']; ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <table>
                                <tr>
                                    <td class="blue">Company Name:</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['origpers']; ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <table>
                                <tr>
                                    <td class="blue">Street Address:</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['origperadd1']; ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <table>
                                <tr>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['origperadd2']; ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:60%;">
                            <table>
                                <tr>
                                    <td class="blue">City:</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['origtown']; ?>
                                        , <?= $collectionParams['details']['origperadd3']; ?></td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:20%;">
                            <table>
                                <tr>
                                    <td class="blue">Country:</td>
                                </tr>
                                <tr>
                                    <td class="black">South Africa</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:20%;">
                            <table>
                                <tr>
                                    <td class="blue">Postal Code:</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['origperpcode']; ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; padding:0 0 0 3px; vertical-align: top;">
                <table class="red" style="width: 100%;">
                    <tr>
                        <td style="width: 50%;">
                            <table>
                                <tr>
                                    <td class="blue">To (Contact Name):</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['destpercontact']; ?></td>
                                </tr>
                            </table>
                        </td>
                        <td colspan="2" style="width: 50%;">
                            <table>
                                <tr>
                                    <td class="blue">Contact Phone Number (Very Important):</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['destperphone']; ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <table>
                                <tr>
                                    <td class="blue">Company Name:</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['destpers']; ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <table>
                                <tr>
                                    <td class="blue">Exact Street Address (We cannot deliver to Box Numbers):</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['destperadd1']; ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <table>
                                <tr>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['destperadd2']; ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="width:60%;">
                            <table>
                                <tr>
                                    <td class="blue">City:</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['desttown']; ?>
                                        , <?= $collectionParams['details']['destperadd3']; ?></td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:20%;">
                            <table>
                                <tr>
                                    <td class="blue">Country:</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= WC()->countries->countries[$shippingItem['shipping_country']]; ?></td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:20%;">
                            <table>
                                <tr>
                                    <td class="blue">Postal Code:</td>
                                </tr>
                                <tr>
                                    <td class="black"><?= $collectionParams['details']['destperpcode']; ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table style="margin-bottom:5px; width: 100%;">
        <tr>
            <td style="padding:0 3px 0 0; width: 50%; vertical-align: top;">
                <table class="blue">
                    <tr>
                        <td colspan="5" class="red" style="padding:4px">SPECIAL INSTRUCTIONS:</td>
                    </tr>
                    <tr>
                        <td colspan="5" class="red" style="padding:3px">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="red">NUMBER</td>
                        <td class="red">DESCRIPTION OF CONTENTS</td>
                        <td class="red">ACTUAL WEIGHT</td>
                        <td colspan="2" class="red">DIMENSIONS (cm)</td>
                    </tr>
                    <?php
                    foreach ($parcels as $parcel) {
                        ?>
                        <tr>
                            <td class="black" style="width: 10%; text-align:center;"><?= $parcel['item']; ?></td>
                            <td class="black" style="width: 30%; text-align:center;"><?= $parcel['desc']; ?></td>
                            <td class="black" style="width: 10%; text-align:center;"><?= $parcel['actmass']; ?></td>
                            <td style="min-width: 40%; max-width: 40%;">
                                <table>
                                    <tr>
                                        <td class="black" style="min-width: 26%; max-width: 26%; text-align:center;"><?= $parcel['dim1']; ?></td>
                                        <td class="red" style="min-width: 10%; max-width: 10%; text-align:center;">x
                                        </td>
                                        <td class="black" style="min-width: 28%; max-width: 28%; text-align:center;"><?= $parcel['dim2']; ?></td>
                                        <td class="red" style="min-width: 10%; max-width: 10%; text-align:center;">x
                                        </td>
                                        <td class="black" style="min-width: 26%; max-width: 26%; text-align:center;"><?= $parcel['dim3']; ?></td>
                                    </tr>
                                </table>
                            </td>
                            <td class="black" style="width: 10%; text-align:center;">X</td>
                        </tr>
                        <?php
                    }
                    $hasInsurance = false;
                    if (get_post_meta($order->get_id(), '_billing_insurance', true) || get_post_meta($order->get_id(), '_shipping_insurance', true)) {
                        $hasInsurance = true;
                    }
                    ?>
                    <tr>
                        <td colspan="3" class="bluebg" style="text-align:center">By virtue of the clients signature
                            hereto,
                            the client
                            acknowledges having read, understood, and agreed to be bound by the standard conditions of
                            carriage of The Courier Guy, which standard conditions are annexed hereto.
                        </td>
                        <td colspan="2">
                            <table style="width: 100%;">
                                <tr>
                                    <td class="red" style="width: 40%;">INSURANCE</td>
                                    <td class="red" style="width: 15%; text-align:right">Y</td>
                                    <td style="width: 15%;">
                                        <table>
                                            <tr>
                                                <td style="width:10px; height: 10px; border:1px solid #32378b; text-align:center" class="black"><?= ($hasInsurance) ? 'X' : ''; ?></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="red" style="width: 15%; text-align:right">N</td>
                                    <td style="width: 15%;">
                                        <table>
                                            <tr>
                                                <td style="width:10px; height: 10px; border:1px solid #32378b; text-align:center" class="black"><?= ($hasInsurance) ? '' : 'X'; ?></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="red" colspan="5" style="text-align:center">
                                        <small>(ONLY DECLARE VALUE IF YES)</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="red">DECLARED<br>VALUE</td>
                                    <td class="red" style="text-align:right">R</td>
                                    <td class="red" colspan="3">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="padding:0 0 0 3px; width: 50%; vertical-align: top;">
                <table class="blue" style="width: 100%;">
                    <tr>
                        <td class="red" colspan="6">SERVICES REQUIRED: Please tick
                            appropriate box(es)
                        </td>
                        <td class="red" colspan="3">
                            <table>
                                <tr>
                                    <td style="width: 34%;">CHARGES</td>
                                    <td style="width: 33%; text-align:center">R</td>
                                    <td style="width: 33%; text-align:center">c</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="red" style="width: 5%; text-align:center">1</td>
                        <td class="black" style="width: 5%; text-align:center"><?= ($service == 'LSX') ? 'X' : ''; ?></td>
                        <td class="red" style="max-width: 12%;">SAME DAY EXPRESS</td>
                        <td class="red" style="width: 5%; text-align:center">8</td>
                        <td class="black" style="width: 5%; text-align:center"></td>
                        <td class="red" style="max-width: 12%;">INTERNATIONAL DOCUMENTS</td>
                        <td class="black" style="width: 12%; text-align:center"></td>
                        <td class="black" style="width: 12%; text-align:center"></td>
                        <td class="black" style="width: 12%; text-align:center"></td>
                    </tr>
                    <tr>
                        <td class="red" style="text-align:center">2</td>
                        <td class="black" style="text-align:center"><?= ($service == 'LOF') ? 'X' : ''; ?></td>
                        <td class="red">LOCAL OVERNIGHT DOCS</td>
                        <td class="red" style="text-align:center">9</td>
                        <td class="black" style="text-align:center"></td>
                        <td class="red">INTERNATIONAL PARCELS</td>
                        <td class="black" style="text-align:center"></td>
                        <td class="black" style="text-align:center"></td>
                        <td class="black" style="text-align:center"></td>
                    </tr>
                    <tr>
                        <td class="red" style="text-align:center">3</td>
                        <td class="red" style="text-align:center"><?= ($service == 'LSF') ? 'X' : ''; ?></td>
                        <td class="red">LOCAL SAME DAY DOCS</td>
                        <td class="red" style="text-align:center">10</td>
                        <td class="red" style="text-align:center"></td>
                        <td class="red">INTERNATIONAL AIR FREIGHT</td>
                        <td class="black" style="text-align:center"></td>
                        <td class="black" style="text-align:center"></td>
                        <td class="black" style="text-align:center"></td>
                    </tr>
                    <tr>
                        <td class="red" style="text-align:center">4</td>
                        <td class="red" style="text-align:center"><?= ($service == 'LSE') ? 'X' : ''; ?></td>
                        <td class="red">LOCAL SAME ECONOMY</td>
                        <td class="red" style="text-align:center">11</td>
                        <td class="black" style="text-align:center"></td>
                        <td class="red">AFTER HOURS SERVICE</td>
                        <td class="black" style="text-align:center"></td>
                        <td class="black" style="text-align:center"></td>
                        <td class="black" style="text-align:center"></td>
                    </tr>
                    <tr>
                        <td class="red" style="text-align:center">5</td>
                        <td class="black" style="text-align:center"><?= ($service == 'OVN') ? 'X' : ''; ?></td>
                        <td class="red">OVERNIGHT COURIER</td>
                        <td class="red" style="text-align:center">12</td>
                        <td class="black" style="text-align:center"></td>
                        <td class="red">SATURDAY SERVICE</td>
                        <td class="black" style="text-align:center"></td>
                        <td class="black" style="text-align:center"></td>
                        <td class="black" style="text-align:center"></td>
                    </tr>
                    <tr>
                        <td class="red" style="text-align:center">6</td>
                        <td class="black" style="text-align:center"><?= ($service == 'AIR') ? 'X' : ''; ?></td>
                        <td class="red">DOMESTIC AIR FREIGHT</td>
                        <td class="red" style="text-align:center">13</td>
                        <td class="black" style="text-align:center"></td>
                        <td class="red">EARLY BIRD</td>
                        <td class="red" style="text-align:center">VAT</td>
                        <td class="black" style="text-align:center"></td>
                        <td class="black" style="text-align:center"></td>
                    </tr>
                    <tr>
                        <td class="red" style="text-align:center">7</td>
                        <td class="black" style="text-align:center"><?= ($service == 'ECO') ? 'X' : ''; ?></td>
                        <td class="red">DOMESTIC ROAD FREIGHT</td>
                        <td class="red" style="text-align:center">14</td>
                        <td class="black" style="text-align:center"></td>
                        <td class="red">SPECIAL CHARGES</td>
                        <td class="red" style="text-align:center">TOTAL</td>
                        <td class="black" style="text-align:center"></td>
                        <td class="black" style="text-align:center"></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td style="width: 50%; padding:0 3px 0 0; vertical-align: top;">
                <table class="red" style="width: 100%;">
                    <tr>
                        <td class="blue" colspan="3" style="padding:12px 6px">CLIENT SIGNATURE:</td>
                    </tr>
                    <tr>
                        <td class="blue" style="padding:6px; width:50%">RECEIVED BY THE COURIER GUY:</td>
                        <td style="width:25%;">
                            <table>
                                <tr>
                                    <td style="width: 30%;" class="blue">DATE:</td>
                                    <td style="width: 30%;text-align:center" class="blue">/</td>
                                    <td style="width: 40%;text-align:center" class="blue">/</td>
                                </tr>
                            </table>
                        </td>
                        <td class="blue" style="width:25%;">TIME:</td>
                    </tr>
                </table>


            </td>
            <td style="width: 50%;padding:0 0 0 3px; vertical-align: top;">
                <table class="red" style="width: 100%;">
                    <tr>
                        <td class="blue" style="width:75%; padding:12px 6px">RECEIVERS SIGNATURE:</td>
                        <td style="width:25%;">
                            <table>
                                <tr>
                                    <td style="width: 30%;" class="blue">DATE:</td>
                                    <td style="width: 30%;text-align:center" class="blue">/</td>
                                    <td style="width: 40%;text-align:center" class="blue">/</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="blue" style="padding:6px">PRINT SURNAME & INITIALS:</td>
                        <td class="blue">TIME:</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php
    if ($i == 1) {
        ?>
        <div class="page_break"></div>
        <?php
    } else {
        ?>
        <br/>
        <?php
    }
}
?>
</body>
</html>
