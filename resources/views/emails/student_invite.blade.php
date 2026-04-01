<!DOCTYPE html>
<html>
<head>
    <title>Placement Test Invitation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Hello {{ $student->name }},</h2>
    
    <p>You have been invited by <strong>{{ $student->organization->name }}</strong> to take the Speaking Genie language placement test.</p>
    
    <p>To begin your test, please click the link below:</p>
    
    <p style="margin: 30px 0;">
        <a href="{{ $inviteLink }}" style="background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold;">
            Start My Test
        </a>
    </p>

    <p>If the button doesn't work, copy and paste this URL into your browser:<br>
    <a href="{{ $inviteLink }}">{{ $inviteLink }}</a></p>

    <p>Good luck!</p>
</body>
</html>
