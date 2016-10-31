![gharpay.in](http://yourstory.in/wp-content/uploads/2011/11/GharpayLogo.png)
## How to install the Extension
1. Download the package _Gharpay_Extension-0.1.0.tgz_ from [downloads page](https://github.com/gharpay/Gharpay-Magento-Plugin/downloads)
2. Go to your magento portal’s admin. i.e. www.yourcompanyurl.com/admin
3. Login with administrator credentials
4. Go to System >> Magento Connect >> Magento Connect  Manager
5. Enter your Admin or Administrator credentials again and login
6. In Magento Connect Manager, go to Direct package file upload
7. Browse or Choose the package file downloaded (Gharpay_Extension-0.1.0.tgz)
8. Click upload
9. The page will inform you when the installation is completed.

## Configuring the Extension
1. Go to System>>Configuration>>Payment Methods(available in left side bar at bottom)>>Gharpay Cash Payment
2. Enter the Webservice URI or URL provided to you by Gharpay in Gharpay Uri field. This should be http://services.gharpay.in in the testing environment. Please get in touch with us at tech@gharpay.in for production URLs.
3. Enter the Webservice API Key (or Username) and API Secret(or Password) provided to you by Gharpay
4. Click “Save Config”
5. Go to System>>Cache Management
6. Important: Select all 7 Cache and in Actions  select Refresh and Submit

Now in store’s checkout page you should see an option to pay using Gharpay.

## Push Notification

Push Notifications are configured by default in the package. For example, if your website URL is http://www.example.com , the default push notification link will be http://www.example.com/gharpaypushnotification
or
http://www.example.com/index.php/gharpaypushnotification

Please note that this is not configurable at the moment.

## Other Functionalities
### View Order Status
When a new order is created using Gharpay’s payment methods, you should be able to see Gharpay’s order status in the new column (Gharpay Status) in the order grid.
### Cancel Order
You can cancel Gharpay’s orders in the same way as you cancel orders belonging to other payment gateways. Just select any Gharpay order, click “Cancel” from the drop down on the top right side of the order grid. Click “Submit” to confirm.

Presently Gharpay Magento Extension is compatible with Magento 1.5 and later versions.
