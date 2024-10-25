<!DOCTYPE html>
              <html lang="en">
              <head>
                  <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                  <title>New Contact Notification</title>
              </head>
              <body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4;">
                  <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-collapse: collapse;">

                      <tr>
                          <td style="padding: 20px;">
                              <p style="margin: 0;">Dear  {{$data['name']}} </p>
                              <p style="margin-top: 10px;">We received your message and will get back to you as soon as possible.</p>
                              <p style="margin-top: 10px;">In the meantime, feel free to explore our website for more information about our products and services.</p>
                              <p style="margin-top: 20px;">Best regards,</p>
                              <p style="margin-top: 5px;">Team Hariom Engineering Pvt. Ltd. </p>
                          </td>
                      </tr>
                      <tr>
                          <td style="padding: 20px; text-align: center; background-color: #f4f4f4;">
                              <p style="margin: 0; font-size: 12px; color: #888;">This is an auto-generated email. Please do not reply.</p>
                          </td>
                      </tr>
                  </table>
              </body>
              </html>
