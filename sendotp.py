#!/usr/bin/env python3
import sys
import smtplib
from email.mime.text import MIMEText

def send_otp(receiver_email, otp):
    """
    Sends an OTP to the receiver_email via Gmail SMTP using STARTTLS (port 587).
    """
    smtp_server = "smtp.gmail.com"
    smtp_port   = 587                # Use 587 for STARTTLS
    smtp_user   = "aragroupakash@gmail.com"
    smtp_pass   = "hynkcrjapkkhueal"  # App Password if you have 2FA or 'less secure' turned off

    subject = "Your BookHeaven OTP Code"
    body    = f"Your OTP code is: {otp}\n\nIf you didn't request this, please ignore."

    msg = MIMEText(body)
    msg["Subject"] = subject
    msg["From"]    = smtp_user
    msg["To"]      = receiver_email

    # Use SMTP + STARTTLS
    with smtplib.SMTP(smtp_server, smtp_port) as server:
        server.starttls()               # Upgrade the connection to secure
        server.login(smtp_user, smtp_pass)
        server.send_message(msg)

if __name__ == "__main__":
    # Expecting two arguments: email and OTP
    if len(sys.argv) != 3:
        print("Usage: python sendotp.py <email> <otp>")
        sys.exit(1)

    email = sys.argv[1]
    otp   = sys.argv[2]
    send_otp(email, otp)
