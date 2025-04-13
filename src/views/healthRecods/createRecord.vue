<script setup>
import { ref, onMounted, onUnmounted, watch} from 'vue';
import { useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const router = useRouter();
const submitting = ref(false);
const submitError = ref(null);
const healthRecord = ref({
  diseaseName: '',
  healthCondition: '',
  haemoglobinValue: '',
  bloodSugarLevel: { min: '', max: '' },
  healthRecordId: '',
  healthChallenges: [],
  diseaseDuration: '',
  medicationName: '',
  medicationIntakeMethod: '',
  userId: '' // Field to store the assigned user ID
});

// Available users to assign the record to
const users = ref([]);
const loadingUsers = ref(false);
const userSearchQuery = ref('');
const filteredUsers = ref([]);
const showUserDropdown = ref(false);

// Health condition options
const healthConditionOptions = [
  'Good',
  'Stable',
  'Moderate',
  'Concerning',
  'Critical',
  'Severe'
];

// New health challenge input
const newChallenge = ref('');

// Current user
const currentUser = ref(null);

// Generate a random health record ID
const generateRecordId = () => {
  const prefix = 'HR';
  const timestamp = Date.now().toString().slice(-6);
  const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
  return `${prefix}-${timestamp}-${random}`;
};

// Get current user
const fetchCurrentUser = async () => {
  try {
    const user = Parse.User.current();
    if (user) {
      currentUser.value = {
        id: user.id,
        name: user.get('username') || 'User',
        email: user.get('email') || ''
      };
    }
  } catch (err) {
    console.error('Error fetching current user:', err);
  }
};

const fetchUsers = async () => {
  loadingUsers.value = true;
  try {
    const results = await Parse.Cloud.run('getAllUsers');
    console.log('Fetched users:', results);
    users.value = results;
    filteredUsers.value = [...users.value];
  } catch (err) {
    console.error('Error fetching users:', err);
    alert('You do not have permission to view this users list.');
  } finally {
    loadingUsers.value = false;
  }
};
// Filter users based on search query
const filterUsers = () => {
  if (!userSearchQuery.value) {
    filteredUsers.value = [...users.value];
    return;
  }

  const query = userSearchQuery.value.toLowerCase();
  filteredUsers.value = users.value.filter(user =>
    user.username.toLowerCase().includes(query) ||
    user.email.toLowerCase().includes(query) ||
    user.fullName.toLowerCase().includes(query)
  );
};

// Set selected user
const selectUser = (user) => {
  healthRecord.value.userId = user.id;
  healthRecord.value.selectedUser = user;
  showUserDropdown.value = false;
};

// Remove selected user
const removeSelectedUser = () => {
  healthRecord.value.userId = '';
  healthRecord.value.selectedUser = null;
};

// Submit form
const submitForm = async () => {
  submitting.value = true;
  submitError.value = null;

  try {
    // Create a new Parse object
    const HealthRecord = Parse.Object.extend('HealthRecord');
    const record = new HealthRecord();

    // Set all the form values
    record.set('diseaseName', healthRecord.value.diseaseName);
    record.set('healthCondition', healthRecord.value.healthCondition);
    record.set('haemoglobinValue', healthRecord.value.haemoglobinValue);
    record.set('bloodSugarLevel', healthRecord.value.bloodSugarLevel);
    record.set('healthRecordId', healthRecord.value.healthRecordId || generateRecordId());
    record.set('healthChallenges', healthRecord.value.healthChallenges);
    record.set('diseaseDuration', healthRecord.value.diseaseDuration);
    record.set('medicationName', healthRecord.value.medicationName);
    record.set('medicationIntakeMethod', healthRecord.value.medicationIntakeMethod);

    // Set the user pointer if a user is selected
    if (healthRecord.value.userId) {
      const userPointer = Parse.User.createWithoutData(healthRecord.value.userId);
      record.set('user', userPointer);
    } else if (Parse.User.current()) {
      // Default to current user if no specific user is selected
      record.set('user', Parse.User.current());
    }

    // Save the new record
    const savedRecord = await record.save();

    // Redirect to detail view
    router.push(`/health-records/${savedRecord.id}`);
  } catch (err) {
    console.error('Error creating health record:', err);
    submitError.value = 'Failed to create health record. Please try again.';
  } finally {
    submitting.value = false;
  }
};

// Add health challenge
const addHealthChallenge = () => {
  if (newChallenge.value.trim() && !healthRecord.value.healthChallenges.includes(newChallenge.value.trim())) {
    healthRecord.value.healthChallenges.push(newChallenge.value.trim());
    newChallenge.value = '';
  }
};

// Remove health challenge
const removeHealthChallenge = (index) => {
  healthRecord.value.healthChallenges.splice(index, 1);
};

// Generate sample record ID
const generateSampleId = () => {
  healthRecord.value.healthRecordId = generateRecordId();
};

// Cancel creation
const cancelCreate = () => {
  router.push('/health-records');
};

// Prefill with sample data for quick testing
const fillSampleData = () => {
  healthRecord.value = {
    diseaseName: 'Type 2 Diabetes',
    healthCondition: 'Moderate',
    haemoglobinValue: '13.5 g/dL',
    bloodSugarLevel: { min: '110 mg/dL', max: '180 mg/dL' },
    healthRecordId: generateRecordId(),
    healthChallenges: ['Frequent urination', 'Increased thirst', 'Fatigue'],
    diseaseDuration: '2 years',
    medicationName: 'Metformin',
    medicationIntakeMethod: 'Oral, twice daily with meals',
    userId: '',
    selectedUser: null
  };
};

// Watch for changes in search query
watch(userSearchQuery, () => {
  filterUsers();
});

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (showUserDropdown.value && !event.target.closest('.user-dropdown-container')) {
    showUserDropdown.value = false;
  }
};

// Load current user and available users on component mount
onMounted(() => {
  fetchCurrentUser();
  fetchUsers();
  generateSampleId();
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
    <div class="max-w-4xl mx-auto">
      <!-- Create Form -->
      <div class="animate-fadeIn">
        <!-- Header -->
        <div class="mb-6 overflow-hidden bg-white shadow-md rounded-xl">
          <div class="relative">
            <!-- Banner background with gradient overlay -->
            <div class="h-40 bg-gradient-to-r from-green-700 to-green-500">
              <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            </div>

            <!-- Content positioned over the banner -->
            <div class="absolute top-0 left-0 flex flex-col justify-center w-full h-full px-6">
              <div class="flex items-center justify-between w-full">
                <div>
                  <div class="flex items-center mb-2 space-x-2">
                    <span class="px-3 py-1 text-sm font-medium text-white bg-white rounded-full bg-opacity-20">
                      New Record
                    </span>
                  </div>
                  <h1 class="text-3xl font-bold text-white">Create Health Record</h1>
                  <p class="mt-1 text-green-100">Add a new health record to the system</p>
                </div>

                <div class="flex space-x-3">
                  <button
                    @click="cancelCreate"
                    class="flex items-center px-4 py-2 text-white bg-white rounded-lg bg-opacity-20 backdrop-blur-sm hover:bg-opacity-30"
                  >
                    <i class="mr-2 pi pi-times"></i>
                    Cancel
                  </button>
                  <button
                    @click="fillSampleData"
                    class="flex items-center px-4 py-2 text-white bg-blue-500 rounded-lg bg-opacity-80 hover:bg-opacity-100"
                  >
                    <i class="mr-2 pi pi-database"></i>
                    Sample Data
                  </button>
                  <button
                    @click="submitForm"
                    :disabled="submitting"
                    class="flex items-center px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-70"
                  >
                    <i v-if="!submitting" class="mr-2 pi pi-check"></i>
                    <div v-else class="w-4 h-4 mr-2 border-t-2 border-b-2 border-white rounded-full animate-spin"></div>
                    {{ submitting ? 'Creating...' : 'Create Record' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Error Alert -->
        <div v-if="submitError" class="p-4 mb-6 text-red-700 bg-red-100 rounded-lg">
          <div class="flex items-center">
            <i class="mr-3 text-xl pi pi-exclamation-circle"></i>
            <p>{{ submitError }}</p>
          </div>
        </div>

        <!-- Form Content -->
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- User Assignment Section -->
          <div class="p-6 bg-white shadow-sm rounded-xl">
            <h2 class="flex items-center mb-4 text-xl font-semibold text-gray-800">
              <i class="mr-2 text-green-600 pi pi-user"></i>
              User Assignment
            </h2>

            <div class="mb-4">
              <label class="block mb-1 text-sm font-medium text-gray-700">Assign to User</label>

              <!-- User Search & Selection -->
              <div class="relative user-dropdown-container">
                <!-- Selected User Display -->
                <div v-if="healthRecord.selectedUser" class="flex items-center p-2 mb-3 bg-green-100 rounded-lg">
                  <div class="flex items-center justify-center w-8 h-8 mr-2 text-white bg-green-600 rounded-full">
                    {{ healthRecord.selectedUser.username.charAt(0).toUpperCase() }}
                  </div>
                  <div class="flex-1">
                    <div class="font-medium">{{ healthRecord.selectedUser.username }}</div>
                    <div class="text-sm text-gray-600">{{ healthRecord.selectedUser.email }}</div>
                  </div>
                  <button
                    type="button"
                    @click="removeSelectedUser"
                    class="p-1 ml-2 text-gray-500 rounded-full hover:bg-green-200"
                  >
                    <i class="pi pi-times"></i>
                  </button>
                </div>

                <!-- Search Input -->
                <div class="relative">
                  <div class="flex items-center px-3 py-2 border rounded-lg">
                    <i class="mr-2 text-gray-400 pi pi-search"></i>
                    <input
                      type="text"
                      v-model="userSearchQuery"
                      placeholder="Search users by name or email"
                      class="flex-1 bg-transparent focus:outline-none"
                      @focus="showUserDropdown = true"
                    />
                  </div>

                  <!-- Dropdown Results -->
                  <div
                    v-if="showUserDropdown"
                    class="absolute z-10 w-full mt-1 overflow-y-auto bg-white border rounded-lg shadow-lg max-h-64"
                  >
                    <div v-if="loadingUsers" class="flex items-center justify-center p-4">
                      <div class="w-5 h-5 border-t-2 border-b-2 border-green-600 rounded-full animate-spin"></div>
                      <span class="ml-2 text-sm text-gray-600">Loading users...</span>
                    </div>

                    <div v-else-if="filteredUsers.length === 0" class="p-3 text-sm text-gray-500">
                      No users found matching your search
                    </div>

                    <div v-else>
                      <div
                        v-for="user in filteredUsers"
                        :key="user.id"
                        @click="selectUser(user)"
                        class="flex items-center p-2 cursor-pointer hover:bg-gray-100"
                      >
                        <div class="flex items-center justify-center w-8 h-8 mr-2 text-white bg-green-600 rounded-full">
                          {{ user.username.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                          <div class="font-medium">{{ user.username }}</div>
                          <div class="text-sm text-gray-600">{{ user.email }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <p class="mt-2 text-sm text-gray-500">
                <i class="text-yellow-500 pi pi-info-circle"></i>
                If no user is selected, the record will not be assigned to any user.
              </p>
            </div>
          </div>

          <!-- Basic Information Section -->
          <div class="p-6 bg-white shadow-sm rounded-xl">
            <h2 class="flex items-center mb-4 text-xl font-semibold text-gray-800">
              <i class="mr-2 text-green-600 pi pi-info-circle"></i>
              Basic Information
            </h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <!-- Disease Name -->
              <div>
                <label for="diseaseName" class="block mb-1 text-sm font-medium text-gray-700">Disease Name</label>
                <input
                  id="diseaseName"
                  v-model="healthRecord.diseaseName"
                  type="text"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500"
                  placeholder="Enter disease name"
                  required
                />
              </div>

              <!-- Health Condition -->
              <div>
                <label for="healthCondition" class="block mb-1 text-sm font-medium text-gray-700">Health Condition</label>
                <select
                  id="healthCondition"
                  v-model="healthRecord.healthCondition"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500"
                  required
                >
                  <option value="" disabled>Select condition</option>
                  <option v-for="condition in healthConditionOptions" :key="condition" :value="condition">
                    {{ condition }}
                  </option>
                </select>
              </div>

              <!-- Record ID -->
              <div>
                <label for="healthRecordId" class="block mb-1 text-sm font-medium text-gray-700">
                  Health Record ID
                  <button
                    type="button"
                    @click="generateSampleId"
                    class="ml-2 text-xs text-green-600 hover:text-green-800"
                  >
                    Generate ID
                  </button>
                </label>
                <input
                  id="healthRecordId"
                  v-model="healthRecord.healthRecordId"
                  type="text"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500"
                  placeholder="Enter record ID or generate one"
                />
              </div>

              <!-- Disease Duration -->
              <div>
                <label for="diseaseDuration" class="block mb-1 text-sm font-medium text-gray-700">Disease Duration</label>
                <input
                  id="diseaseDuration"
                  v-model="healthRecord.diseaseDuration"
                  type="text"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500"
                  placeholder="E.g., 6 months, 2 years"
                />
              </div>
            </div>
          </div>

          <!-- Health Metrics Section -->
          <div class="p-6 bg-white shadow-sm rounded-xl">
            <h2 class="flex items-center mb-4 text-xl font-semibold text-gray-800">
              <i class="mr-2 text-green-600 pi pi-chart-line"></i>
              Health Metrics
            </h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <!-- Haemoglobin Value -->
              <div>
                <label for="haemoglobinValue" class="block mb-1 text-sm font-medium text-gray-700">Haemoglobin Value</label>
                <input
                  id="haemoglobinValue"
                  v-model="healthRecord.haemoglobinValue"
                  type="text"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500"
                  placeholder="Enter haemoglobin value"
                />
              </div>
            </div>

            <!-- Blood Sugar Levels -->
            <div class="mt-4">
              <label class="block mb-1 text-sm font-medium text-gray-700">Blood Sugar Level Range</label>
              <div class="flex items-center space-x-3">
                <div class="flex-1">
                  <input
                    v-model="healthRecord.bloodSugarLevel.min"
                    type="text"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500"
                    placeholder="Minimum"
                  />
                </div>
                <span class="text-gray-500">to</span>
                <div class="flex-1">
                  <input
                    v-model="healthRecord.bloodSugarLevel.max"
                    type="text"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500"
                    placeholder="Maximum"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Health Challenges Section -->
          <div class="p-6 bg-white shadow-sm rounded-xl">
            <h2 class="flex items-center mb-4 text-xl font-semibold text-gray-800">
              <i class="mr-2 text-green-600 pi pi-exclamation-triangle"></i>
              Health Challenges
            </h2>

            <div class="mb-4">
              <label class="block mb-1 text-sm font-medium text-gray-700">Add Health Challenges</label>
              <div class="flex space-x-2">
                <input
                  v-model="newChallenge"
                  type="text"
                  class="flex-1 px-3 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500"
                  placeholder="Enter a health challenge"
                  @keyup.enter="addHealthChallenge"
                />
                <button
                  type="button"
                  @click="addHealthChallenge"
                  class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
                >
                  Add
                </button>
              </div>
            </div>

            <!-- Challenges List -->
            <div class="mt-4">
              <h3 class="mb-2 text-sm font-medium text-gray-700">Current Health Challenges</h3>

              <div v-if="healthRecord.healthChallenges && healthRecord.healthChallenges.length > 0" class="flex flex-wrap gap-2">
                <div
                  v-for="(challenge, index) in healthRecord.healthChallenges"
                  :key="index"
                  class="flex items-center px-3 py-1 text-sm text-gray-700 bg-gray-100 rounded-full"
                >
                  {{ challenge }}
                  <button
                    type="button"
                    @click="removeHealthChallenge(index)"
                    class="ml-2 text-gray-500 hover:text-red-600"
                  >
                    <i class="pi pi-times"></i>
                  </button>
                </div>
              </div>

              <p v-else class="text-sm italic text-gray-500">No health challenges added yet</p>
            </div>
          </div>

          <!-- Medication Section -->
          <div class="p-6 bg-white shadow-sm rounded-xl">
            <h2 class="flex items-center mb-4 text-xl font-semibold text-gray-800">
              <i class="mr-2 text-green-600 pi pi-inbox"></i>
              Medication
            </h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <!-- Medication Name -->
              <div>
                <label for="medicationName" class="block mb-1 text-sm font-medium text-gray-700">Medication Name</label>
                <input
                  id="medicationName"
                  v-model="healthRecord.medicationName"
                  type="text"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500"
                  placeholder="Enter medication name"
                />
              </div>

              <!-- Intake Method -->
              <div>
                <label for="medicationIntakeMethod" class="block mb-1 text-sm font-medium text-gray-700">Intake Method</label>
                <input
                  id="medicationIntakeMethod"
                  v-model="healthRecord.medicationIntakeMethod"
                  type="text"
                  class="w-full px-3 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500"
                  placeholder="E.g., Oral, Injection"
                />
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end space-x-4">
            <button
              type="button"
              @click="cancelCreate"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="flex items-center px-6 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-70"
            >
              <div v-if="submitting" class="w-4 h-4 mr-2 border-t-2 border-b-2 border-white rounded-full animate-spin"></div>
              {{ submitting ? 'Creating...' : 'Create Record' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
