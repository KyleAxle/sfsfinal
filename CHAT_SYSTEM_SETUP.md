# Chat System Setup Guide

## Overview
A real-time messaging system has been added to allow users and staff to communicate with each other.

## Database Setup

1. **Create the messages table:**
   ```bash
   php create_messages_table.php
   ```
   
   Or run the SQL manually in your Supabase SQL Editor:
   ```sql
   CREATE TABLE IF NOT EXISTS public.messages (
       message_id       BIGSERIAL PRIMARY KEY,
       sender_type      VARCHAR(20) NOT NULL CHECK (sender_type IN ('user', 'staff')),
       sender_user_id   BIGINT REFERENCES public.users (user_id) ON DELETE SET NULL,
       sender_staff_id  BIGINT REFERENCES public.staff (staff_id) ON DELETE SET NULL,
       recipient_type   VARCHAR(20) NOT NULL CHECK (recipient_type IN ('user', 'staff')),
       recipient_user_id BIGINT REFERENCES public.users (user_id) ON DELETE SET NULL,
       recipient_staff_id BIGINT REFERENCES public.staff (staff_id) ON DELETE SET NULL,
       message          TEXT NOT NULL,
       is_read          BOOLEAN NOT NULL DEFAULT FALSE,
       created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
       CONSTRAINT messages_sender_check
           CHECK (
               (sender_type = 'user' AND sender_user_id IS NOT NULL AND sender_staff_id IS NULL)
               OR (sender_type = 'staff' AND sender_staff_id IS NOT NULL AND sender_user_id IS NULL)
           ),
       CONSTRAINT messages_recipient_check
           CHECK (
               (recipient_type = 'user' AND recipient_user_id IS NOT NULL AND recipient_staff_id IS NULL)
               OR (recipient_type = 'staff' AND recipient_staff_id IS NOT NULL AND recipient_user_id IS NULL)
           ),
       CONSTRAINT messages_not_self
           CHECK (
               NOT (sender_type = recipient_type AND sender_user_id = recipient_user_id)
               AND NOT (sender_type = recipient_type AND sender_staff_id = recipient_staff_id)
           )
   );
   ```

## Features

### For Users (Client Dashboard)
- Access chat via "Messages" link in the sidebar
- View all conversations with staff members
- Start new conversations with any staff member
- Send and receive messages in real-time (auto-refreshes every 3 seconds)
- See unread message counts

### For Staff (Staff Dashboard)
- Access chat via "Messages" link in the sidebar
- View all conversations with users
- Start new conversations with any user
- Send and receive messages in real-time (auto-refreshes every 3 seconds)
- See unread message counts

## API Endpoints

### `send_message.php`
- **Method:** POST
- **Body:** JSON
  ```json
  {
    "sender_type": "user" | "staff",
    "recipient_type": "user" | "staff",
    "recipient_id": <number>,
    "message": "<text>"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "message_id": <number>,
    "created_at": "<timestamp>"
  }
  ```

### `get_messages.php`
- **Method:** GET
- **Parameters:**
  - `other_type`: "user" | "staff"
  - `other_id`: <number>
- **Response:**
  ```json
  {
    "success": true,
    "messages": [
      {
        "message_id": <number>,
        "sender_type": "user" | "staff",
        "message": "<text>",
        "created_at": "<timestamp>",
        "is_read": <boolean>,
        "sender_name": "<name>"
      }
    ]
  }
  ```

### `get_conversations.php`
- **Method:** GET
- **Response:**
  ```json
  {
    "success": true,
    "conversations": [
      {
        "other_id": <number>,
        "other_type": "user" | "staff",
        "other_name": "<name>",
        "last_message": "<text>",
        "last_message_time": "<timestamp>",
        "unread_count": <number>
      }
    ],
    "available_staff": [...],  // Only for users
    "available_users": [...]    // Only for staff
  }
  ```

## UI Components

### Client Dashboard (`client_dashboard.html`)
- Chat section with conversation list and message panel
- Modal for starting new conversations with staff

### Staff Dashboard (`staff_dashboard.php`)
- Chat section with conversation list and message panel
- Modal for starting new conversations with users

## Styling

Chat styling is included in:
- `assets/css/dashboard.css` - For client dashboard
- `assets/css/staff_dashboard.css` - For staff dashboard

## How It Works

1. **Authentication:** Uses existing session variables (`user_id` for users, `staff_id` for staff)
2. **Real-time Updates:** Polls for new messages every 3 seconds when a conversation is open
3. **Read Status:** Messages are automatically marked as read when viewed
4. **Conversation List:** Shows the latest message and unread count for each conversation

## Testing

1. Log in as a user
2. Click "Messages" in the sidebar
3. Click "+ New Message" to start a conversation with a staff member
4. Send a message
5. Log in as staff (in another browser/incognito)
6. Click "Messages" in the sidebar
7. You should see the conversation with the unread message
8. Reply to the message
9. Switch back to the user view - the reply should appear automatically

## Notes

- Messages are stored permanently in the database
- The system prevents users from messaging themselves
- Messages are automatically scrolled to the bottom when loaded
- Enter key sends message (Shift+Enter for new line)
- The chat interface is responsive and works on mobile devices

