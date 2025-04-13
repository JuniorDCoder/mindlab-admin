<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const error = ref(null);
const healthRecord = ref(null);
const deleteDialogVisible = ref(false);
const deleting = ref(false);
const deleteError = ref(null);

// Fetch health record details
const fetchHealthRecord = async () => {
  const id = route.params.id;
  if (!id) {
    error.value = 'No health record ID provided';
    loading.value = false;
    return;
  }

  try {
    const HealthRecord = Parse.Object.extend('HealthRecord');
    const query = new Parse.Query(HealthRecord);

    // Include related user
    query.include('user');

    const result = await query.get(id);

    if (!result) {
      throw new Error('Health record not found');
    }

    console.log("Fetched health record:", result.id);

    // Transform the data
    healthRecord.value = {
      id: result.id,
      diseaseName: result.get('diseaseName') || 'Unnamed Condition',
      healthCondition: result.get('healthCondition') || 'Not specified',
      haemoglobinValue: result.get('haemoglobinValue') || 'N/A',
      bloodSugarLevel: result.get('bloodSugarLevel') || { min: 'N/A', max: 'N/A' },
      healthRecordId: result.get('healthRecordId') || 'N/A',
      healthChallenges: result.get('healthChallenges') || [],
      diseaseDuration: result.get('diseaseDuration') || 'N/A',
      medicationName: result.get('medicationName') || 'None',
      medicationIntakeMethod: result.get('medicationIntakeMethod') || 'N/A',
      createdAt: result.createdAt,
      updatedAt: result.updatedAt,
      user: result.get('user') ? {
        id: result.get('user').id,
        name: result.get('user').get('username') || 'Unknown User',
        email: result.get('user').get('email') || ''
      } : null,
      parseObject: result // Store the Parse object for deletion
    };

  } catch (err) {
    console.error('Error fetching health record:', err);
    if (err.code === Parse.Error.OBJECT_NOT_FOUND) {
      error.value = 'Health record not found. It may have been deleted.';
    } else {
      error.value = 'Failed to load health record. Please try again later.';
    }
  } finally {
    loading.value = false;
  }
};

// Format date to readable format
const formatDate = (date) => {
  if (!date) return 'N/A';

  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Edit health record
const editRecord = () => {
  if (healthRecord.value) {
    router.push(`/health-records/${healthRecord.value.id}/edit`);
  }
};

// Show delete confirmation dialog
const confirmDelete = () => {
  deleteDialogVisible.value = true;
};

// Delete health record
const deleteRecord = async () => {
  if (!healthRecord.value || !healthRecord.value.parseObject) return;

  deleting.value = true;
  deleteError.value = null;

  try {
    await healthRecord.value.parseObject.destroy();

    // Redirect to list after successful deletion
    router.push('/health-records');
  } catch (err) {
    console.error('Error deleting health record:', err);
    deleteError.value = 'Failed to delete health record. Please try again.';
    deleting.value = false;
  }
};

// Cancel delete
const cancelDelete = () => {
  deleteDialogVisible.value = false;
  deleteError.value = null;
};

// Get status class based on health condition
const getHealthStatusClass = (condition) => {
  if (!condition) return 'bg-gray-100 text-gray-700';

  const lowercased = condition.toLowerCase();
  if (lowercased.includes('critical') || lowercased.includes('severe')) {
    return 'bg-red-100 text-red-700';
  } else if (lowercased.includes('moderate') || lowercased.includes('concerning')) {
    return 'bg-yellow-100 text-yellow-700';
  } else if (lowercased.includes('good') || lowercased.includes('normal') || lowercased.includes('stable')) {
    return 'bg-green-100 text-green-700';
  } else {
    return 'bg-blue-100 text-blue-700';
  }
};

// Get full status class for larger status elements
const getFullHealthStatusClass = (condition) => {
  if (!condition) return 'bg-gray-50 text-gray-700 border-gray-200';

  const lowercased = condition.toLowerCase();
  if (lowercased.includes('critical') || lowercased.includes('severe')) {
    return 'bg-red-50 text-red-700 border-red-200';
  } else if (lowercased.includes('moderate') || lowercased.includes('concerning')) {
    return 'bg-yellow-50 text-yellow-700 border-yellow-200';
  } else if (lowercased.includes('good') || lowercased.includes('normal') || lowercased.includes('stable')) {
    return 'bg-green-50 text-green-700 border-green-200';
  } else {
    return 'bg-blue-50 text-blue-700 border-blue-200';
  }
};

// Load data on component mount
onMounted(() => {
  fetchHealthRecord();
});
</script>

<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
    <div class="max-w-4xl mx-auto">
      <!-- Loading state -->
      <div v-if="loading" class="flex items-center justify-center h-64">
        <div class="w-16 h-16 border-t-4 border-b-4 border-green-600 rounded-full animate-spin"></div>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="p-8 bg-white shadow-md rounded-xl">
        <div class="flex items-center justify-center">
          <div class="flex items-center p-4 text-red-700 bg-red-100 rounded-lg">
            <i class="mr-3 text-2xl pi pi-exclamation-circle"></i>
            <div>
              <h3 class="font-medium">Error</h3>
              <p>{{ error }}</p>
            </div>
          </div>
        </div>
        <div class="mt-6 text-center">
          <button
            @click="() => router.push('/health-records')"
            class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
          >
            Back to Health Records
          </button>
        </div>
      </div>

      <!-- Health Record Details -->
      <div v-else-if="healthRecord" class="animate-fadeIn">
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
                    <span class="px-3 py-1 text-sm font-medium rounded-full" :class="getHealthStatusClass(healthRecord.healthCondition)">
                      {{ healthRecord.healthCondition }}
                    </span>
                    <span class="px-3 py-1 text-sm font-medium text-gray-700 bg-gray-100 rounded-full">
                      Record ID: {{ healthRecord.healthRecordId }}
                    </span>
                  </div>
                  <h1 class="text-3xl font-bold text-white">{{ healthRecord.diseaseName }}</h1>
                  <p class="mt-1 text-green-100">Last updated: {{ formatDate(healthRecord.updatedAt) }}</p>
                </div>

                <div class="flex space-x-3">
                  <button
                    @click="() => router.push('/health-records')"
                    class="flex items-center px-4 py-2 text-white bg-white rounded-lg bg-opacity-20 backdrop-blur-sm hover:bg-opacity-30"
                  >
                    <i class="mr-2 pi pi-arrow-left"></i>
                    Back
                  </button>
                  <button
                    @click="editRecord"
                    class="flex items-center px-4 py-2 text-white bg-white rounded-lg bg-opacity-20 backdrop-blur-sm hover:bg-opacity-30"
                  >
                    <i class="mr-2 pi pi-pencil"></i>
                    Edit
                  </button>
                  <button
                    @click="confirmDelete"
                    class="flex items-center px-4 py-2 text-white bg-red-500 rounded-lg bg-opacity-80 hover:bg-opacity-100"
                  >
                    <i class="mr-2 pi pi-trash"></i>
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-3">
          <!-- Left Column - Health Status & Information -->
          <div class="space-y-6 lg:col-span-2">
            <!-- Health Status Card -->
            <div class="p-6 bg-white shadow-sm rounded-xl">
              <h2 class="flex items-center mb-4 text-xl font-semibold text-gray-800">
                <i class="mr-2 text-green-600 pi pi-heart-fill"></i>
                Health Status
              </h2>

              <div class="p-4 mb-4 border rounded-lg" :class="getFullHealthStatusClass(healthRecord.healthCondition)">
                <div class="flex items-center">
                  <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 rounded-full"
                       :class="getHealthStatusClass(healthRecord.healthCondition)">
                    <i class="text-xl pi pi-heart-fill"></i>
                  </div>
                  <div class="ml-4">
                    <h3 class="text-lg font-semibold">{{ healthRecord.healthCondition }}</h3>
                    <p class="text-sm">
                      <span v-if="healthRecord.healthCondition.toLowerCase().includes('critical')">
                        Requires immediate medical attention
                      </span>
                      <span v-else-if="healthRecord.healthCondition.toLowerCase().includes('moderate')">
                        Regular monitoring recommended
                      </span>
                      <span v-else-if="healthRecord.healthCondition.toLowerCase().includes('stable')">
                        Condition is under control
                      </span>
                      <span v-else>
                        Current health assessment
                      </span>
                    </p>
                  </div>
                </div>
              </div>

              <!-- Health Metrics -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Blood Values -->
                <div class="p-4 border rounded-lg">
                  <h3 class="mb-3 text-sm font-semibold text-gray-500 uppercase">Haemoglobin</h3>
                  <p class="text-2xl font-bold text-gray-800">{{ healthRecord.haemoglobinValue }}</p>
                </div>

                <!-- Blood Sugar -->
                <div class="p-4 border rounded-lg">
                  <h3 class="mb-3 text-sm font-semibold text-gray-500 uppercase">Blood Sugar Range</h3>
                  <div class="flex items-baseline space-x-2">
                    <p class="text-2xl font-bold text-gray-800">{{ healthRecord.bloodSugarLevel.min }}</p>
                    <span class="text-lg text-gray-500">-</span>
                    <p class="text-2xl font-bold text-gray-800">{{ healthRecord.bloodSugarLevel.max }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Disease Information -->
            <div class="p-6 bg-white shadow-sm rounded-xl">
              <h2 class="flex items-center mb-4 text-xl font-semibold text-gray-800">
                <i class="mr-2 text-green-600 pi pi-book"></i>
                Disease Information
              </h2>

              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <h3 class="mb-1 text-sm font-semibold text-gray-500">Disease Name</h3>
                  <p class="text-lg text-gray-800">{{ healthRecord.diseaseName }}</p>
                </div>

                <div>
                  <h3 class="mb-1 text-sm font-semibold text-gray-500">Duration</h3>
                  <p class="text-lg text-gray-800">{{ healthRecord.diseaseDuration }}</p>
                </div>
              </div>

              <!-- Health Challenges -->
              <div class="pt-4 mt-4 border-t">
                <h3 class="mb-3 text-sm font-semibold text-gray-500">Health Challenges</h3>

                <div v-if="healthRecord.healthChallenges && healthRecord.healthChallenges.length > 0" class="flex flex-wrap gap-2">
                  <span
                    v-for="challenge in healthRecord.healthChallenges"
                    :key="challenge"
                    class="px-3 py-1 text-sm text-gray-700 bg-gray-100 rounded-full"
                  >
                    {{ challenge }}
                  </span>
                </div>

                <p v-else class="text-sm italic text-gray-500">No specific health challenges recorded</p>
              </div>
            </div>
          </div>

          <!-- Right Column - Medication & Patient Info -->
          <div class="space-y-6">
            <!-- Medication Card -->
            <div class="p-6 bg-white shadow-sm rounded-xl">
              <h2 class="flex items-center mb-4 text-xl font-semibold text-gray-800">
                <i class="mr-2 text-green-600 pi pi-inbox"></i>
                Medication
              </h2>

              <div class="p-4 border border-blue-100 rounded-lg bg-blue-50">
                <div class="flex items-start mb-3">
                  <div class="p-2 bg-blue-100 rounded-md">
                    <i class="text-xl text-blue-600 pi pi-medicine"></i>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-lg font-medium text-gray-800">{{ healthRecord.medicationName }}</h3>
                    <p class="text-sm text-gray-600">Current medication</p>
                  </div>
                </div>

                <div class="pt-3 mt-3 border-t border-blue-200">
                  <h4 class="mb-1 text-sm font-medium text-gray-500">Intake Method</h4>
                  <p class="text-gray-800">{{ healthRecord.medicationIntakeMethod }}</p>
                </div>
              </div>
            </div>

            <!-- Patient Details -->
            <div v-if="healthRecord.user" class="p-6 bg-white shadow-sm rounded-xl">
              <h2 class="flex items-center mb-4 text-xl font-semibold text-gray-800">
                <i class="mr-2 text-green-600 pi pi-user"></i>
                Patient Information
              </h2>

              <div class="flex items-center p-4 border rounded-lg">
                <div class="flex items-center justify-center w-12 h-12 text-green-600 bg-green-100 rounded-full">
                  <i class="text-xl pi pi-user"></i>
                </div>
                <div class="ml-3">
                  <h3 class="text-lg font-medium text-gray-800">{{ healthRecord.user.name }}</h3>
                  <p v-if="healthRecord.user.email" class="text-gray-600">{{ healthRecord.user.email }}</p>
                </div>
              </div>
            </div>

            <!-- Record Information -->
            <div class="p-6 bg-white shadow-sm rounded-xl">
              <h2 class="flex items-center mb-4 text-xl font-semibold text-gray-800">
                <i class="mr-2 text-green-600 pi pi-calendar"></i>
                Record Information
              </h2>

              <div class="space-y-3">
                <div>
                  <h3 class="text-sm font-medium text-gray-500">Record ID</h3>
                  <p class="text-gray-800">{{ healthRecord.healthRecordId }}</p>
                </div>

                <div>
                  <h3 class="text-sm font-medium text-gray-500">Created</h3>
                  <p class="text-gray-800">{{ formatDate(healthRecord.createdAt) }}</p>
                </div>

                <div>
                  <h3 class="text-sm font-medium text-gray-500">Last Updated</h3>
                  <p class="text-gray-800">{{ formatDate(healthRecord.updatedAt) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons (Bottom) -->
        <div class="flex justify-end mt-6 space-x-4">
          <button
            @click="() => router.push('/health-records')"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Back to List
          </button>

          <button
            @click="editRecord"
            class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700"
          >
            Edit Record
          </button>

          <button
            @click="confirmDelete"
            class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700"
          >
            Delete Record
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div v-if="deleteDialogVisible" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50">
      <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-xl">
        <h3 class="mb-4 text-lg font-medium text-gray-900">Confirm Deletion</h3>

        <p class="mb-4 text-gray-700">
          Are you sure you want to delete the health record for
          <span class="font-medium">{{ healthRecord?.diseaseName }}</span>?
          This action cannot be undone.
        </p>

        <!-- Error message -->
        <div v-if="deleteError" class="p-3 mb-4 text-red-700 bg-red-100 rounded-md">
          <div class="flex items-center">
            <i class="mr-2 pi pi-exclamation-circle"></i>
            <p>{{ deleteError }}</p>
          </div>
        </div>

        <div class="flex justify-end space-x-3">
          <button
            @click="cancelDelete"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
            :disabled="deleting"
          >
            Cancel
          </button>

          <button
            @click="deleteRecord"
            class="flex items-center px-4 py-2 text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50"
            :disabled="deleting"
          >
            <div v-if="deleting" class="w-4 h-4 mr-2 border-t-2 border-b-2 border-white rounded-full animate-spin"></div>
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
