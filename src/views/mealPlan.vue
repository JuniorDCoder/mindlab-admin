<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import Parse from 'parse/dist/parse.min.js';

const router = useRouter();
const mealPlans = ref([]);
const loading = ref(true);
const error = ref(null);
const showDeleteModal = ref(false);
const selectedPlan = ref(null);
const searchQuery = ref('');
const sortKey = ref('updatedAt');
const sortDirection = ref('desc');
const pageSize = 10;
const currentPage = ref(1);

// Filtered and sorted meal plans
const filteredMealPlans = computed(() => {
  if (!mealPlans.value.length) return [];

  // Filter by search query
  let filtered = mealPlans.value;
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(plan =>
      plan.name.toLowerCase().includes(query) ||
      plan.description.toLowerCase().includes(query)
    );
  }

  // Sort results
  filtered = [...filtered].sort((a, b) => {
    let aValue = a[sortKey.value];
    let bValue = b[sortKey.value];

    // Handle dates
    if (sortKey.value.includes('Date') || sortKey.value.includes('At')) {
      aValue = new Date(aValue);
      bValue = new Date(bValue);
    }

    if (sortDirection.value === 'asc') {
      return aValue > bValue ? 1 : -1;
    } else {
      return aValue < bValue ? 1 : -1;
    }
  });

  return filtered;
});

// Paginated meal plans
const paginatedMealPlans = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  const end = start + pageSize;
  return filteredMealPlans.value.slice(start, end);
});

// Total pages
const totalPages = computed(() => {
  return Math.ceil(filteredMealPlans.value.length / pageSize);
});

// Format date for display
const formatDate = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(date);
};

// Fetch all meal plans
const fetchMealPlans = async () => {
  loading.value = true;
  error.value = null;

  try {
    const MealPlan = Parse.Object.extend('MealPlans');
    const query = new Parse.Query(MealPlan);

    // Include related meal objects
    query.include('meal');
    // Include user info
    query.include('user');
    // Sort by updated date descending (newest first)
    query.descending('updatedAt');

    const results = await query.find();

    // Transform Parse objects to plain objects
    mealPlans.value = results.map(plan => {
      const planData = plan.toJSON();

      // Extract meal info if available
      if (planData.meal) {
        planData.mealName = planData.meal.name;
        planData.mealImage = planData.meal.image ? planData.meal.image.url : null;
      }

      // Extract user info if available
      if (planData.user) {
        planData.userName = planData.user.username || 'Unknown User';
      }

      return planData;
    });
  } catch (err) {
    console.error('Error fetching meal plans:', err);
    error.value = 'Failed to load meal plans. Please try again.';
  } finally {
    loading.value = false;
  }
};

// Handle sort change
const changeSort = (key) => {
  if (sortKey.value === key) {
    // Toggle direction if same key
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    // New key, default to desc
    sortKey.value = key;
    sortDirection.value = 'desc';
  }
};

// Navigate to view meal plan details
const viewMealPlan = (id) => {
  router.push(`/meal-plan/${id}`);
};

// Navigate to edit meal plan
const editMealPlan = (id) => {
  router.push(`/meal-plan/edit/${id}`);
};

// Open delete confirmation modal
const confirmDelete = (plan) => {
  selectedPlan.value = plan;
  showDeleteModal.value = true;
};

// Delete meal plan
const deleteMealPlan = async () => {
  if (!selectedPlan.value) return;

  try {
    const MealPlan = Parse.Object.extend('MealPlans');
    const query = new Parse.Query(MealPlan);
    const planToDelete = await query.get(selectedPlan.value.objectId);

    await planToDelete.destroy();

    // Remove from local state
    mealPlans.value = mealPlans.value.filter(
      plan => plan.objectId !== selectedPlan.value.objectId
    );

    // Close modal
    showDeleteModal.value = false;
    selectedPlan.value = null;
  } catch (err) {
    console.error('Error deleting meal plan:', err);
    error.value = 'Failed to delete meal plan. Please try again.';
  }
};

// Handle pagination
const goToPage = (page) => {
  if (page < 1 || page > totalPages.value) return;
  currentPage.value = page;
};

// Create a new meal plan
const createMealPlan = () => {
  router.push('/meal-plan/create');
};

// Load meal plans on component mount
onMounted(() => {
  fetchMealPlans();
});
</script>

<template>
  <div class="min-h-screen px-4 py-8 bg-green-50 md:ml-[20%]">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="flex flex-col mb-6 md:flex-row md:items-center md:justify-between">
        <h1 class="mb-4 text-3xl font-bold text-green-800 md:mb-0">Meal Plans</h1>

        <div class="flex flex-col gap-4 sm:flex-row">
          <!-- Search bar -->
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search meal plans..."
              class="w-full py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
            />
            <div class="absolute left-3 top-2.5 text-gray-400">
              <i class="pi pi-search"></i>
            </div>
          </div>

          <!-- Create button -->
          <button
            @click="createMealPlan"
            class="flex items-center justify-center px-4 py-2 font-medium text-white transition bg-green-600 rounded-lg hover:bg-green-700"
          >
            <i class="mr-2 pi pi-plus"></i>
            <span>Create Plan</span>
          </button>
        </div>
      </div>

      <!-- Error message -->
      <div v-if="error" class="p-4 mb-6 border-l-4 border-red-500 rounded-r-lg bg-red-50">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="text-red-500 pi pi-exclamation-triangle"></i>
          </div>
          <div class="ml-3">
            <p class="text-sm text-red-700">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="flex items-center justify-center h-64">
        <div class="w-16 h-16 border-t-4 border-b-4 border-green-600 rounded-full animate-spin"></div>
      </div>

      <!-- Empty state -->
      <div v-else-if="mealPlans.length === 0" class="p-8 text-center bg-white shadow-md rounded-xl">
        <div class="inline-flex items-center justify-center p-4 mb-4 text-green-600 bg-green-100 rounded-full">
          <i class="text-3xl pi pi-calendar-plus"></i>
        </div>
        <h3 class="mb-2 text-xl font-medium text-gray-900">No Meal Plans Found</h3>
        <p class="mb-4 text-gray-600">Get started by creating your first meal plan.</p>
        <button
          @click="createMealPlan"
          class="px-4 py-2 font-medium text-white transition bg-green-600 rounded-lg hover:bg-green-700"
        >
          Create Your First Plan
        </button>
      </div>

      <!-- Meal plans table -->
      <div v-else class="overflow-hidden bg-white shadow-md rounded-xl">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase cursor-pointer" @click="changeSort('name')">
                  <div class="flex items-center">
                    <span>Name</span>
                    <i class="ml-1 pi" :class="{
                      'pi-arrow-up': sortKey === 'name' && sortDirection === 'asc',
                      'pi-arrow-down': sortKey === 'name' && sortDirection === 'desc',
                      'opacity-0': sortKey !== 'name'
                    }"></i>
                  </div>
                </th>
                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase cursor-pointer" @click="changeSort('startDate')">
                  <div class="flex items-center">
                    <span>Start Date</span>
                    <i class="ml-1 pi" :class="{
                      'pi-arrow-up': sortKey === 'startDate' && sortDirection === 'asc',
                      'pi-arrow-down': sortKey === 'startDate' && sortDirection === 'desc',
                      'opacity-0': sortKey !== 'startDate'
                    }"></i>
                  </div>
                </th>
                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase cursor-pointer" @click="changeSort('endDate')">
                  <div class="flex items-center">
                    <span>End Date</span>
                    <i class="ml-1 pi" :class="{
                      'pi-arrow-up': sortKey === 'endDate' && sortDirection === 'asc',
                      'pi-arrow-down': sortKey === 'endDate' && sortDirection === 'desc',
                      'opacity-0': sortKey !== 'endDate'
                    }"></i>
                  </div>
                </th>
                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                  Meal
                </th>
                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase cursor-pointer" @click="changeSort('updatedAt')">
                  <div class="flex items-center">
                    <span>Last Updated</span>
                    <i class="ml-1 pi" :class="{
                      'pi-arrow-up': sortKey === 'updatedAt' && sortDirection === 'asc',
                      'pi-arrow-down': sortKey === 'updatedAt' && sortDirection === 'desc',
                      'opacity-0': sortKey !== 'updatedAt'
                    }"></i>
                  </div>
                </th>
                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="plan in paginatedMealPlans" :key="plan.objectId" class="transition-colors hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ plan.name }}</div>
                  <div class="max-w-xs text-xs text-gray-500 truncate">{{ plan.description }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                  {{ plan.startDate ? formatDate(plan.startDate.iso) : 'Not set' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                  {{ plan.endDate ? formatDate(plan.endDate.iso) : 'Not set' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="plan.meal" class="flex items-center">
                    <div v-if="plan.mealImage" class="flex-shrink-0 w-10 h-10">
                      <img class="object-cover w-10 h-10 rounded-full" :src="plan.mealImage" alt="" />
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ plan.mealName }}</div>
                    </div>
                  </div>
                  <span v-else class="inline-flex px-2 text-xs font-semibold leading-5 text-gray-800 bg-gray-100 rounded-full">
                    No meal assigned
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                  {{ formatDate(plan.updatedAt) }}
                </td>
                <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                  <div class="flex justify-end space-x-2">
                    <button @click="viewMealPlan(plan.objectId)" class="text-green-600 hover:text-green-900" title="View">
                      <i class="pi pi-eye"></i>
                    </button>
                    <button @click="editMealPlan(plan.objectId)" class="text-blue-600 hover:text-blue-900" title="Edit">
                      <i class="pi pi-pencil"></i>
                    </button>
                    <button @click="confirmDelete(plan)" class="text-red-600 hover:text-red-900" title="Delete">
                      <i class="pi pi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
          <div class="flex justify-between flex-1 sm:hidden">
            <button
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage === 1"
              :class="[
                'relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50',
                { 'opacity-50 cursor-not-allowed': currentPage === 1 }
              ]"
            >
              Previous
            </button>
            <button
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              :class="[
                'relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50',
                { 'opacity-50 cursor-not-allowed': currentPage === totalPages }
              ]"
            >
              Next
            </button>
          </div>
          <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing <span class="font-medium">{{ ((currentPage - 1) * pageSize) + 1 }}</span> to
                <span class="font-medium">{{ Math.min(currentPage * pageSize, filteredMealPlans.length) }}</span> of
                <span class="font-medium">{{ filteredMealPlans.length }}</span> results
              </p>
            </div>
            <div>
              <nav class="inline-flex -space-x-px rounded-md shadow-sm isolate" aria-label="Pagination">
                <button
                  @click="goToPage(currentPage - 1)"
                  :disabled="currentPage === 1"
                  :class="[
                    'relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                    { 'opacity-50 cursor-not-allowed': currentPage === 1 }
                  ]"
                >
                  <i class="pi pi-chevron-left"></i>
                </button>

                <!-- Page number buttons -->
                <template v-for="page in totalPages" :key="page">
                  <!-- Display first page, current page, and last page, with ellipses for others -->
                  <button
                    v-if="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
                    @click="goToPage(page)"
                    :class="[
                      'relative inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                      currentPage === page ? 'z-10 bg-green-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600' : 'text-gray-900'
                    ]"
                  >
                    {{ page }}
                  </button>
                  <!-- Ellipsis before current page -->
                  <span
                    v-else-if="page === currentPage - 2 && page > 1"
                    class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300"
                  >
                    ...
                  </span>
                  <!-- Ellipsis after current page -->
                  <span
                    v-else-if="page === currentPage + 2 && page < totalPages"
                    class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300"
                  >
                    ...
                  </span>
                </template>

                <button
                  @click="goToPage(currentPage + 1)"
                  :disabled="currentPage === totalPages"
                  :class="[
                    'relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                    { 'opacity-50 cursor-not-allowed': currentPage === totalPages }
                  ]"
                >
                  <i class="pi pi-chevron-right"></i>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 transition-opacity bg-black bg-opacity-50" @click="showDeleteModal = false"></div>

    <!-- Modal -->
    <div class="relative max-w-md mx-auto transition-all transform bg-white rounded-lg shadow-xl sm:w-full sm:max-w-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Confirm Deletion</h3>
      </div>

      <div class="px-6 py-4">
        <div class="flex items-center mb-4 text-red-600">
          <i class="mr-2 text-2xl pi pi-exclamation-triangle"></i>
          <p class="text-gray-700">
            Are you sure you want to delete the meal plan <strong>{{ selectedPlan?.name }}</strong>?
            <br>This action cannot be undone.
          </p>
        </div>
      </div>

      <div class="flex justify-end px-6 py-4 space-x-3 border-t border-gray-200">
        <button
          @click="showDeleteModal = false"
          class="px-4 py-2 text-gray-800 bg-gray-200 rounded hover:bg-gray-300"
        >
          Cancel
        </button>
        <button
          @click="deleteMealPlan"
          class="px-4 py-2 text-white bg-red-600 rounded hover:bg-red-700"
        >
          Delete
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pi {
  font-family: 'PrimeIcons', sans-serif;
}

/* Transition effects */
.list-move,
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* Ensure items being moved away during removal don't affect layout */
.list-leave-active {
  position: absolute;
}
</style>
