<!DOCTYPE html>
<html>
    <head>
        <title>Example request from ZooZ IPN</title>
        
        <script src="sha256.min.js"></script>
        <script src="//code.jquery.com/jquery-1.12.0.min.js"></script>
        <script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
        
        <style type="text/css">
            div { clear: both; }
            input { display: block; float: left; clear: both; width: 400px; margin-bottom: 5px; padding: 2px 4px; }
            input[type="submit"] { width: 150px; }
            .special { font-weight: bold; }
        </style>
    </head>
    <body>
        <div>
            <form id="ipnForm" action="" method="post">
                <input type="text" name="programId" placeholder="programId" value="PAPI_ZooZNP_TS2YCLIPYYRNBNMMOHUUMNOZFM_1" />
                <input type="text" name="amount" placeholder="amount" value="10.00" />
                <input type="text" name="refundAmount" placeholder="refundAmount" value="0" />
                <input type="text" name="currencyCode" placeholder="currencyCode" value="USD" />
                <input type="text" name="ipAddress" placeholder="ipAddress" value="54.200.242.136" />
                <input type="text" name="isSandbox" placeholder="isSandbox" value="true" />
                <input type="text" name="paymentId" placeholder="paymentId" value="" />
                <input type="text" name="paymentToken" placeholder="paymentToken" value="" />
                <input type="text" name="paymentMethodLastUsedTimestamp" placeholder="paymentMethodLastUsedTimestamp" value="1416905715472" />
                <input type="text" name="paymentMethodToken" placeholder="paymentMethodToken" value="" />
                <input type="text" name="processorReferenceId" placeholder="processorReferenceId" value="006199" />
                <input type="text" name="merchantServerApiKey" placeholder="merchantServerApiKey" value="0532741d-bc8e-4ae8-8aa3-cbc2b9e55b4e" />
                <input type="text" name="paymentStatus" placeholder="paymentStatus" value="Payment Authorized, Pending completion" />
                <input type="text" name="paymentStatusCode" placeholder="paymentStatusCode" value="1002" />                
                <input type="text" name="invoiceNumber" placeholder="invoiceNumber" value="" />
                <input class="special" type="text" name="signature" placeholder="signature" value="" />
                <div>
                    <input type="submit" class="send" value="send" />
                </div>
            </form>
        </div>
            
        <script type="text/javascript">
            $( document ).ready(function() {

                var data = getFormData();
                var signature = calculateSignature(data);
                var json = prepareJson(data, signature);

                jQuery("input[name='signature']").val(signature);

                $('input').keyup(function() {

                    var data = getFormData();
                    var signature = calculateSignature(data);
                    var json = prepareJson(data, signature);

                    jQuery("input[name='signature']").val(signature);
                });

                $('#ipnForm').submit(function() {
                    $.ajax({
                        url: '/payments/ipn',
                        method: 'POST',
                        data: json,
                        dataType: 'json'
                    });
                    return false; 
                });

                function getFormData() {
                    var data = new Array();
                    data.paymentId = $("input[name='paymentId']").val();
                    data.paymentToken = $("input[name='paymentToken']").val();
                    data.paymentMethodLastUsedTimestamp = $("input[name='paymentMethodLastUsedTimestamp']").val();
                    data.paymentMethodToken = $("input[name='paymentMethodToken']").val();
                    data.processorReferenceId = $("input[name='processorReferenceId']").val();
                    data.merchantServerApiKey = $("input[name='merchantServerApiKey']").val();
                    
                    data.invoiceNumber = $("input[name='invoiceNumber']").val();
                    
                    data.programId = $("input[name='programId']").val();
                    data.amount = $("input[name='amount']").val();
                    data.currencyCode = $("input[name='currencyCode']").val();
                    data.ipAddress = $("input[name='ipAddress']").val();
                    data.isSandbox = $("input[name='isSandbox']").val();
                    data.refundAmount = $("input[name='refundAmount']").val();
                    data.paymentStatus = $("input[name='paymentStatus']").val();
                    data.paymentStatusCode = $("input[name='paymentStatusCode']").val();
                    data.merchantServerApiKey = $("input[name='merchantServerApiKey']").val();
                    data.signature = $("input[name='signature']").val();

                    return data;
                };

                function calculateSignature(data) {
                    var signature = sha256(data.paymentId + data.paymentMethodLastUsedTimestamp + data.paymentMethodToken + data.processorReferenceId + data.merchantServerApiKey);
                    return signature;
                };

                function prepareJson(data) {
                    var obj = new Object();
                    obj.paymentMethod = new Object();
                    obj.invoice = new Object();

                    obj.programId = data.programId;
                    obj.amount = data.amount;
                    obj.currencyCode = data.currencyCode;
                    obj.ipAddress = data.ipAddress;
                    obj.isSandbox = data.isSandbox;
                    obj.paymentId = data.paymentId;
                    obj.paymentToken = data.paymentToken;
                    obj.paymentMethod.paymentMethodLastUsedTimestamp = data.paymentMethodLastUsedTimestamp;
                    obj.paymentMethod.paymentMethodToken = data.paymentMethodToken;
                    obj.invoice.number = data.invoiceNumber;
                    obj.paymentStatus = data.paymentStatus;
                    obj.paymentStatusCode = data.paymentStatusCode;
                    obj.signature = data.signature;
                    
                    var jsonString= JSON.stringify(obj);

                    return jsonString;
                }
            });
        </script>
    </body>
</html>