<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const router = useRouter();
const loading = ref(true);
const error = ref(null);
const healthRecords = ref([]);
const filterQuery = ref('');
const currentFilter = ref('all');
const deleteDialogVisible = ref(false);
const recordToDelete = ref(null);
const deleting = ref(false);
const deleteError = ref(null);

// Fetch all health records
const fetchHealthRecords = async () => {
  loading.value = true;
  error.value = null;

  try {
    const HealthRecord = Parse.Object.extend('HealthRecord');
    const query = new Parse.Query(HealthRecord);

    // Include related user object
    query.include('user');

    // Sort by most recently updated
    query.descending('updatedAt');

    // Apply search filter if any
    if (filterQuery.value) {
      const diseasenameQuery = new Parse.Query(HealthRecord);
      diseasenameQuery.matches('diseaseName', new RegExp(filterQuery.value, 'i'));

      const healthConditionQuery = new Parse.Query(HealthRecord);
      healthConditionQuery.matches('healthCondition', new RegExp(filterQuery.value, 'i'));

      const medicationQuery = new Parse.Query(HealthRecord);
      medicationQuery.matches('medicationName', new RegExp(filterQuery.value, 'i'));

      const mainQuery = Parse.Query.or(diseasenameQuery, healthConditionQuery, medicationQuery);
      query._orQuery(mainQuery);
    }

    const results = await query.find();
    console.log(`Fetched ${results.length} health records`);

    healthRecords.value = results.map(record => ({
      id: record.id,
      diseaseName: record.get('diseaseName') || 'Unnamed Condition',
      healthCondition: record.get('healthCondition') || 'Not specified',
      haemoglobinValue: record.get('haemoglobinValue') || 'N/A',
      bloodSugarLevel: record.get('bloodSugarLevel') || { min: 'N/A', max: 'N/A' },
      healthRecordId: record.get('healthRecordId') || 'N/A',
      healthChallenges: record.get('healthChallenges') || [],
      diseaseDuration: record.get('diseaseDuration') || 'N/A',
      medicationName: record.get('medicationName') || 'None',
      medicationIntakeMethod: record.get('medicationIntakeMethod') || 'N/A',
      createdAt: record.createdAt,
      updatedAt: record.updatedAt,
      user: record.get('user') ? {
        id: record.get('user').id,
        name: record.get('user').get('username') || 'Unknown User',
        email: record.get('user').get('email') || ''
      } : null,
      parseObject: record // Store the Parse object for deletion
    }));
  } catch (err) {
    console.error('Error fetching health records:', err);
    error.value = 'Failed to load health records. Please try again later.';
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

// Apply filter
const applyFilter = (filter) => {
  currentFilter.value = filter;
  fetchHealthRecords();
};

// Handle search input
const handleSearch = () => {
  fetchHealthRecords();
};

// View health record details
const viewRecord = (id) => {
  router.push(`/health-records/${id}`);
};

// Edit health record
const editRecord = (id) => {
  router.push(`/health-records/${id}/edit`);
};

// Show delete confirmation dialog
const confirmDelete = (record) => {
  recordToDelete.value = record;
  deleteDialogVisible.value = true;
};

// Delete health record
const deleteRecord = async () => {
  if (!recordToDelete.value) return;

  deleting.value = true;
  deleteError.value = null;

  try {
    await recordToDelete.value.parseObject.destroy();

    // Remove from list
    healthRecords.value = healthRecords.value.filter(record => record.id !== recordToDelete.value.id);

    // Close dialog
    deleteDialogVisible.value = false;
    recordToDelete.value = null;
  } catch (err) {
    console.error('Error deleting health record:', err);
    deleteError.value = 'Failed to delete health record. Please try again.';
  } finally {
    deleting.value = false;
  }
};

// Cancel delete
const cancelDelete = () => {
  deleteDialogVisible.value = false;
  recordToDelete.value = null;
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

// Load data on component mount
onMounted(() => {
  fetchHealthRecords();
});
</script>

<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%] lg:ml-[20%]">
    <div class="max-w-6xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6 overflow-hidden bg-white shadow-md rounded-xl">
        <div class="relative">
          <!-- Banner background with gradient overlay -->
          <div class="h-32 bg-gradient-to-r from-green-700 to-green-500">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
          </div>

          <!-- Content positioned over the banner -->
          <div class="absolute top-0 left-0 flex items-center justify-between w-full h-full px-6">
            <h1 class="text-3xl font-bold text-white">Health Records</h1>

            <div class="flex space-x-3">
              <button
                @click="() => router.push('/health-records/new')"
                class="flex items-center px-4 py-2 text-white bg-white rounded-lg bg-opacity-20 backdrop-blur-sm hover:bg-opacity-30"
              >
                <i class="mr-2 pi pi-plus"></i>
                New Record
              </button>
            </div>
          </div>
        </div>

        <!-- Filter and Search Controls -->
        <div class="p-6 border-b">
          <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
              <button
                @click="applyFilter('all')"
                class="px-4 py-2 text-sm rounded-full"
                :class="currentFilter === 'all' ? 'bg-green-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
              >
                All Records
              </button>
              <button
                @click="applyFilter('critical')"
                class="px-4 py-2 text-sm text-red-700 bg-red-100 rounded-full hover:bg-red-200"
                :class="currentFilter === 'critical' ? 'ring-2 ring-red-500' : ''"
              >
                Critical
              </button>
              <button
                @click="applyFilter('stable')"
                class="px-4 py-2 text-sm text-green-700 bg-green-100 rounded-full hover:bg-green-200"
                :class="currentFilter === 'stable' ? 'ring-2 ring-green-500' : ''"
              >
                Stable
              </button>
            </div>

            <!-- Search -->
            <div class="relative w-full md:w-64">
              <input
                v-model="filterQuery"
                type="text"
                placeholder="Search records..."
                class="w-full px-4 py-2 pl-10 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                @keyup.enter="handleSearch"
              >
              <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                <i class="text-gray-400 pi pi-search"></i>
              </div>
              <button
                v-if="filterQuery"
                @click="filterQuery = ''; handleSearch()"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
              >
                <i class="pi pi-times"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center h-64">
        <div class="w-16 h-16 border-t-4 border-b-4 border-green-600 rounded-full animate-spin"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="p-8 mb-6 bg-white shadow-md rounded-xl">
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
            @click="fetchHealthRecords"
            class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
          >
            Try Again
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="healthRecords.length === 0" class="p-8 mb-6 text-center bg-white shadow-md rounded-xl">
        <div class="mx-auto mb-4 text-gray-400 w-14 h-14">
          <i class="text-5xl pi pi-file"></i>
        </div>
        <h3 class="mb-2 text-lg font-medium text-gray-900">No health records found</h3>
        <p class="mb-6 text-gray-500">
          {{ filterQuery ? 'No records match your search criteria.' : 'Get started by creating your first health record.' }}
        </p>
        <button
          @click="() => router.push('/health-records/new')"
          class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700"
        >
          Create Health Record
        </button>
      </div>

      <!-- Health Records List -->
      <div v-else class="grid grid-cols-1 gap-4 animate-fadeIn md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="record in healthRecords"
          :key="record.id"
          class="overflow-hidden transition-shadow duration-200 bg-white shadow-sm rounded-xl hover:shadow-md"
        >
          <!-- Card Header -->
          <div class="px-4 py-3 border-b">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-gray-800 truncate" :title="record.diseaseName">
                {{ record.diseaseName }}
              </h3>

              <!-- Record ID Badge -->
              <span class="px-2 py-1 text-xs bg-gray-100 rounded-full">
                ID: {{ record.healthRecordId }}
              </span>
            </div>

            <!-- Health Status Badge -->
            <div class="flex items-center mt-2">
              <span
                class="px-2 py-1 text-xs font-medium rounded-full"
                :class="getHealthStatusClass(record.healthCondition)"
              >
                {{ record.healthCondition || 'Unknown' }}
              </span>

              <span class="ml-auto text-xs text-gray-500">
                Updated: {{ formatDate(record.updatedAt) }}
              </span>
            </div>
          </div>

          <!-- Card Body -->
          <div class="p-4">
            <!-- Medical Info -->
            <div class="grid grid-cols-2 gap-2 mb-4">
              <div>
                <p class="text-xs font-medium text-gray-500">Haemoglobin</p>
                <p class="text-sm">{{ record.haemoglobinValue }}</p>
              </div>

              <div>
                <p class="text-xs font-medium text-gray-500">Blood Sugar</p>
                <p class="text-sm">
                  {{ record.bloodSugarLevel.min }} - {{ record.bloodSugarLevel.max }}
                </p>
              </div>

              <div>
                <p class="text-xs font-medium text-gray-500">Duration</p>
                <p class="text-sm">{{ record.diseaseDuration }}</p>
              </div>

              <div>
                <p class="text-xs font-medium text-gray-500">Medication</p>
                <p class="text-sm truncate" :title="record.medicationName">{{ record.medicationName }}</p>
              </div>
            </div>

            <!-- Health Challenges -->
            <div v-if="record.healthChallenges && record.healthChallenges.length > 0" class="mb-4">
              <p class="mb-1 text-xs font-medium text-gray-500">Health Challenges</p>
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="(challenge, idx) in record.healthChallenges.slice(0, 3)"
                  :key="idx"
                  class="px-2 py-1 text-xs bg-gray-100 rounded-full"
                >
                  {{ challenge }}
                </span>
                <span
                  v-if="record.healthChallenges.length > 3"
                  class="px-2 py-1 text-xs bg-gray-100 rounded-full"
                >
                  +{{ record.healthChallenges.length - 3 }} more
                </span>
              </div>
            </div>

            <!-- User Info -->
            <div v-if="record.user" class="flex items-center pt-3 mt-3 border-t">
              <div class="flex-1">
                <p class="text-xs font-medium text-gray-500">Patient</p>
                <p class="text-sm text-gray-800">{{ record.user.name }}</p>
              </div>
            </div>
          </div>

          <!-- Card Actions -->
          <div class="flex border-t">
            <button
              @click="viewRecord(record.id)"
              class="flex items-center justify-center flex-1 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
            >
              <i class="mr-1 pi pi-eye"></i> View
            </button>

            <button
              @click="editRecord(record.id)"
              class="flex items-center justify-center flex-1 px-4 py-2 text-sm text-blue-700 border-l hover:bg-blue-50"
            >
              <i class="mr-1 pi pi-pencil"></i> Edit
            </button>

            <button
              @click="confirmDelete(record)"
              class="flex items-center justify-center flex-1 px-4 py-2 text-sm text-red-700 border-l hover:bg-red-50"
            >
              <i class="mr-1 pi pi-trash"></i> Delete
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination (placeholder for future implementation) -->
      <div v-if="healthRecords.length > 0" class="flex items-center justify-between mt-6">
        <p class="text-sm text-gray-600">
          Showing {{ healthRecords.length }} records
        </p>

        <div class="flex">
          <button class="px-3 py-1 text-sm rounded-md hover:bg-gray-200">Previous</button>
          <button class="px-3 py-1 ml-2 text-sm text-white bg-green-600 rounded-md hover:bg-green-700">1</button>
          <button class="px-3 py-1 ml-2 text-sm rounded-md hover:bg-gray-200">Next</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div v-if="deleteDialogVisible" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50">
      <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-xl">
        <h3 class="mb-4 text-lg font-medium text-gray-900">Confirm Deletion</h3>

        <p class="mb-4 text-gray-700">
          Are you sure you want to delete the health record for
          <span class="font-medium">{{ recordToDelete?.diseaseName }}</span>?
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
