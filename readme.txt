 Plugin Name: Synapse India Support
Description: WordPress API functions.
Version:     1.1.1
Author:      SynapseIndia Outsourcing Pvt. Ltd.
Author URI:  https://www.synapseindia.com/

MBA Insurance API Plugin

We have developed a MBA Insurance plugin where we have to first get the Quote of the price via their API
API URL 1: https://mbapartnerconnect.biz/v1/quote/create/

Once the price is fetched the price is send to Woocommerce Checkout page to make the payment.
The insurance plan includes three types of plan with roadside assistance on each plan which the user can opt for it. Hence we have 6types of combination.

Once the payment is made the, the datas are being added to the Woocommerce Order details page as meta fields.

API URL 2: https://mbapartnerconnect.net/v1/addendum/create/

By the above URL the Insurance and the Roadside assistance if opted is generated and the respective price is also deducted from the Account details which is shared by the user.

API URL 3: https://mbapartnerconnect.net/v1/addendum/retrieve/

The respective PDf of Insurance and Roadside is generate by this API link with an additional information that needs to be passed which is “addendum” for fetching Insurance PDF and “roadside” for fetching Roadside assistance.

API URL 4: https://mbapartnerconnect.net/v1/addendum/cancel/

To cancel the insurance and the Roadside Assistance can be cancelled by the above API URL. Now cancellation can only be done before the start journey date, after start journey date it cannot be cancelled. So for this we have added the respective functionality on the API plugin also.

Once the Insurance is cancelled we again need to retrieve the PDF documents to fetch the cancelled PDF via the API URL 4