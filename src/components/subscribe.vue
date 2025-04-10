<!-- eslint-disable vue/multi-word-component-names -->
<script setup>
import { ref, onMounted } from 'vue';

const subscriptions = ref([]);
const isAddFormVisible = ref(false);
const newSubscription = ref({ name: '', price: '', duration: '', description: '' });
const editingSubscriptionId = ref(null);
const editedSubscription = ref({});

const loadSubscriptions = () => {
  const savedSubscriptions = JSON.parse(localStorage.getItem('subscriptions')) || [];
  subscriptions.value = savedSubscriptions;
};

const toggleAddForm = () => {
  isAddFormVisible.value = !isAddFormVisible.value;
};

const addSubscription = () => {
  const subscription = { id: Date.now(), ...newSubscription.value };
  subscriptions.value.push(subscription);
  localStorage.setItem('subscriptions', JSON.stringify(subscriptions.value));
  newSubscription.value = { name: '', price: '', duration: '', description: '' };
  isAddFormVisible.value = false;
};

const startEditing = (subscription) => {
  editingSubscriptionId.value = subscription.id;
  editedSubscription.value = { ...subscription };
};

const saveEditedSubscription = () => {
  const index = subscriptions.value.findIndex(sub => sub.id === editingSubscriptionId.value);
  if (index !== -1) {
    subscriptions.value[index] = { ...editedSubscription.value };
    localStorage.setItem('subscriptions', JSON.stringify(subscriptions.value));
    editingSubscriptionId.value = null;
  }
};

const deleteSubscription = (id) => {
  subscriptions.value = subscriptions.value.filter(sub => sub.id !== id);
  localStorage.setItem('subscriptions', JSON.stringify(subscriptions.value));
};

onMounted(loadSubscriptions);
</script>

<template>
  <div class="w-auto h-screen bg-green-50 ml-52 lg:px-8">
    <div class="fixed top-0 left-0 flex items-center justify-between w-full p-5 bg-green-900 lg:space-x-20 lg:px-20 lg:pl-[20%] lg:ml-10 rounded-br-md">
      <h1 class="text-2xl font-semibold text-white">Manage <span class="text-green-400">Subscriptions</span></h1>
      <button @click="toggleAddForm" class="px-4 py-2 text-white bg-indigo-700 rounded-md">Add New Subscription</button>
    </div>

    <!-- Add Subscription Form -->
    <div v-if="isAddFormVisible" class="fixed inset-0 flex items-center justify-center bg-green-900 bg-opacity-60">
      <form @submit.prevent="addSubscription" class="w-1/2 p-4 bg-white rounded-md shadow-md">
        <h2 class="mb-4 text-lg font-semibold">Add New Subscription</h2>
        <span class="py-2 ">
          <label class="text-gray-800">Subscription type</label>
          <input v-model="newSubscription.name" placeholder="" class="w-full px-4 py-2 mb-2 text-sm text-gray-800 bg-gray-300 border rounded-md" required />
        </span>
        <span class="py-2 ">
          <label class="text-gray-800">Description</label>
          <input v-model="newSubscription.description" placeholder="" class="w-full px-4 py-2 mb-2 text-sm text-gray-800 bg-gray-300 border rounded-md" required />
        </span>
        <span class="py-2 ">
          <label class="text-gray-800">Price</label>
          <input v-model="newSubscription.price" placeholder="" class="w-full px-4 py-2 mb-2 text-sm text-gray-800 bg-gray-300 border rounded-md" required />
        </span>
        <span class="py-2 ">
          <label class="text-gray-800">Duration</label>
          <input v-model="newSubscription.duration" placeholder="" class="w-full px-4 py-2 mb-2 text-sm text-gray-800 bg-gray-300 border rounded-md" required />
        </span>
        <div class="flex justify-between">
          <button type="submit" class="px-4 py-2 text-white bg-green-500 rounded-md">Add</button>
          <button @click="toggleAddForm" type="button" class="px-4 py-2 text-white bg-red-500 rounded-md">Cancel</button>
        </div>
      </form>
    </div>

    <!-- Edit Subscription Form -->
    <div v-if="editingSubscriptionId" class="fixed inset-0 flex items-center justify-center bg-green-900 bg-opacity-60 ">
      <form @submit.prevent="saveEditedSubscription" class="w-1/2 p-10 px-16 bg-white rounded-md shadow-md">
        <h2 class="mb-4 text-lg font-bold">Edit Subscription</h2>
        <span class="py-2 ">
          <label class="text-gray-800">Subscription type</label>
          <input v-model="editedSubscription.name" placeholder="" class="w-full px-4 py-2 mb-2 text-sm text-gray-800 bg-gray-300 border rounded-md" required />
        </span>
        <span class="py-2 ">
          <label class="text-gray-800">Description</label>
          <input v-model="editedSubscription.description" placeholder="" class="w-full px-4 py-2 mb-2 text-sm text-gray-800 bg-gray-300 border rounded-md" required />
        </span>
        <span class="py-2 ">
          <label class="text-gray-800">Price</label>
          <input v-model="editedSubscription.price" placeholder="" class="w-full px-4 py-2 mb-2 text-sm text-gray-800 bg-gray-300 border rounded-md" required />
        </span>
        <span class="py-3 ">
          <label class="text-gray-800">Duration</label>
          <input v-model="editedSubscription.duration" placeholder="" class="w-full px-4 py-2 mb-2 text-sm text-gray-800 bg-gray-300 border rounded-md" required />
        </span>
        <div class="flex justify-between">
          <button type="submit" class="px-4 py-2 text-white bg-green-500 rounded-md">Save</button>
          <button @click="editingSubscriptionId = null" type="button" class="px-4 py-2 text-white bg-red-500 rounded-md">Cancel</button>
        </div>
      </form>
    </div>

    <!-- Subscriptions List -->
    <div class="grid grid-cols-1 gap-6 py-5 md:px-2 sm:grid-cols-3 lg:grid-cols-4 lg:mt-20 animate-fadeIn">
      <div v-for="subscription in subscriptions" :key="subscription.id" class="transition-transform duration-300 rounded-lg shadow-2xl hover:scale-110 bg-gradient-to-br from-green-200 to-gray-300 ">
        <span class="top-0 left-0 p-2 text-sm font-semibold text-gray-700 bg-pink-100 rounded-br-md">Free for 1 month</span>
        <div class="flex py-3 space-x-1 ">
          <i class="mt-1 text-xl text-yellow-400 pi pi-crown"></i>
          <h3 class="text-xl font-bold text-green-500">{{ subscription.name }}</h3>
        </div>
       <div class="p-3">
        <label class="font-semibold">Description</label>
        <li class="text-gray-700">{{ subscription.description }}</li>
       </div>
        <div class="p-3 ">
          <label class="font-bold">Price</label>
          <p class="font-semibold text-blue-500">Price: ${{ subscription.price }}</p>
        </div>
        <div class="p-3 ">
          <p class="font-semibold text-gray-700">Duration: {{ subscription.duration }} Days</p>
        </div>
        <div class="flex px-1 py-4 mt-4 space-x-16 text-center">
          <button @click="startEditing(subscription)" class="px-3 py-1 text-white bg-blue-500 rounded-md"><i class="text-white pi pi-pencil"></i></button>
          <button @click="deleteSubscription(subscription.id)" class="px-3 py-1 text-white bg-red-500 rounded-md"><i class="pi pi-trash"></i></button>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.5s ease-out;
}
</style>
