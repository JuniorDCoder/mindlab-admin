<!-- eslint-disable vue/multi-word-component-names -->
<script setup>
import { ref } from 'vue';

const messages = ref([
  { id: 1, sender: 'user', text: 'How do I book an appointment?', time: '10:00 AM' },
  { id: 2, sender: 'admin', text: 'You can book an appointment through the dashboard.', time: '10:02 AM' },
  { id: 3, sender: 'user', text: 'What are your working hours?', time: '10:05 AM' }
]);

const newMessage = ref('');

const sendMessage = () => {
  if (newMessage.value.trim() === '') return;
  messages.value.push({
    id: messages.value.length + 1,
    sender: 'admin',
    text: newMessage.value,
    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  });
  newMessage.value = '';
};
</script>

<template>
  <div class="flex flex-col h-screen bg-green-50 lg:ml-[20%]">
    <div class="flex items-center justify-between px-6 py-6 text-lg font-bold text-white bg-green-900 ">
      <span class="ml-auto">Community Chat</span>
    </div>
    <div class="flex-1 p-4 space-y-3 overflow-y-auto">
      <div v-for="msg in messages" :key="msg.id"
           class="flex"
           :class="msg.sender === 'admin' ? 'justify-end' : 'justify-start'">
        <div class="max-w-xs p-3 rounded-lg shadow"
             :class="msg.sender === 'admin' ? 'bg-green-700 text-white' : 'bg-white text-gray-700'">
          <p>{{ msg.text }}</p>
          <span class="block mt-1 text-xs text-right">{{ msg.time }}</span>
        </div>
      </div>
    </div>
    <div class="flex items-center p-4 bg-white border-t">
      <input v-model="newMessage"
             type="text"
             placeholder="Type a reply..."
             class="flex-1 p-2 border rounded-lg">
      <button @click="sendMessage" class="px-4 py-2 ml-2 text-white bg-green-600 rounded-lg hover:bg-green-700">Send</button>
    </div>
  </div>
</template>
