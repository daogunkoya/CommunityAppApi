<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;

class ConversationsSeeder extends Seeder
{
    public function run(): void
    {
        // Get some users for testing
        $users = User::limit(5)->get();

        if ($users->count() < 2) {
            $this->command->info('Need at least 2 users to create conversations. Skipping.');
            return;
        }

        // Create a direct conversation between first two users
        $directConversation = Conversation::create([
            'type' => 'direct',
            'name' => null,
        ]);

        // Add participants
        ConversationParticipant::create([
            'conversation_id' => $directConversation->id,
            'user_id' => $users[0]->id,
        ]);
        ConversationParticipant::create([
            'conversation_id' => $directConversation->id,
            'user_id' => $users[1]->id,
        ]);

        // Add some messages
        $message1 = Message::create([
            'conversation_id' => $directConversation->id,
            'user_id' => $users[0]->id,
            'content' => 'Hey! How are you doing?',
        ]);

        $message2 = Message::create([
            'conversation_id' => $directConversation->id,
            'user_id' => $users[1]->id,
            'content' => 'I\'m doing great! How about you?',
        ]);

        $message3 = Message::create([
            'conversation_id' => $directConversation->id,
            'user_id' => $users[0]->id,
            'content' => 'Pretty good! Want to play tennis this weekend?',
        ]);

        // Update conversation with last message
        $directConversation->update(['last_message_id' => $message3->id]);

        // Create a group conversation
        $groupConversation = Conversation::create([
            'type' => 'group',
            'name' => 'Tennis Group',
        ]);

        // Add participants to group
        foreach ($users->take(4) as $user) {
            ConversationParticipant::create([
                'conversation_id' => $groupConversation->id,
                'user_id' => $user->id,
            ]);
        }

        // Add some group messages
        $groupMessage1 = Message::create([
            'conversation_id' => $groupConversation->id,
            'user_id' => $users[0]->id,
            'content' => 'Anyone up for a tennis match this weekend?',
        ]);

        $groupMessage2 = Message::create([
            'conversation_id' => $groupConversation->id,
            'user_id' => $users[1]->id,
            'content' => 'I\'m in! What time works for everyone?',
        ]);

        $groupMessage3 = Message::create([
            'conversation_id' => $groupConversation->id,
            'user_id' => $users[2]->id,
            'content' => 'Saturday morning at 9 AM works for me!',
        ]);

        // Update group conversation with last message
        $groupConversation->update(['last_message_id' => $groupMessage3->id]);

        $this->command->info('Created test conversations with messages!');
    }
}
