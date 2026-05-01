<template>
    <div class="schema-builder" @focusin="handleFocusIn">
        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-0 fw-bold">JSON-LD Schema Builder</h5>
                <p class="text-muted small mb-0">Build structured data markup for search engines</p>
            </div>
            <span v-if="schemaType" class="badge bg-primary fs-6">{{ schemaType }}</span>
        </div>
        <!-- Dynamic value from fieldMap -->
        <div v-if="trackerfieldMap && trackerfieldMap.length" class="tracker-field-insert border rounded p-2 bg-light mb-4">
            <p class="text-muted small mb-1 fw-semibold">
                <i class="bi bi-cursor-text me-1"></i>
                Click a field to insert it at the cursor position in any input below:
            </p>
            <div class="d-flex flex-wrap gap-1">
                <button
                    v-for="field in trackerfieldMap"
                    :key="field.id"
                    @mousedown.prevent
                    @click="insertAtCursor(field.id)"
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    :title="`Insert {{${field.id}}}`"
                >
                    <span class="font-monospace small">{{ field.name }}</span>
                </button>
            </div>
        </div>

        <!-- Schema Type Selector -->
        <div class="mb-4">
            <label class="form-label fw-semibold">Schema Type</label>
            <select v-model="schemaType" class="form-select" @change="resetForm">
                <option value="">— None (disabled) —</option>
                <optgroup v-for="group in schemaTypeGroups" :key="group.label" :label="group.label">
                    <option v-for="type in group.types" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </optgroup>
            </select>
        </div>

        <!-- Dynamic Fields -->
        <div v-if="schemaType && currentSchema" class="mb-4">
            <div class="card">
                <div class="card-header bg-light">
                    <strong>{{ currentSchema.label }} Schema Fields</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <template v-for="field in currentSchema.fields" :key="field.key">

                            <!-- Repeatable field groups (FAQ items, steps, reviews, etc.) -->
                            <div v-if="field.type === 'repeatable'" class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold mb-0">{{ field.label }}</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addRepeatableItem(field.key, field.fields)">
                                        + Add {{ field.itemLabel || 'Item' }}
                                    </button>
                                </div>
                                <p v-if="!formData[field.key] || formData[field.key].length === 0" class="text-muted small fst-italic">
                                    No items added yet. Click "Add {{ field.itemLabel || 'Item' }}" to begin.
                                </p>
                                <div v-for="(item, index) in formData[field.key]" :key="index" class="card mb-2 border-secondary-subtle">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2 bg-light">
                                        <span class="small fw-semibold">{{ field.itemLabel || 'Item' }} {{ index + 1 }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0" @click="removeRepeatableItem(field.key, index)">
                                            &times; Remove
                                        </button>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="row g-2">
                                            <div v-for="subField in field.fields" :key="subField.key"
                                                :class="`col-md-${subField.col || (subField.type === 'textarea' ? 12 : 6)}`">
                                                <label class="form-label small fw-semibold mb-1">{{ subField.label }}</label>
                                                <input
                                                    v-if="['text','url','date','datetime-local','number','email','tel'].includes(subField.type)"
                                                    :type="subField.type"
                                                    v-model="item[subField.key]"
                                                    class="form-control form-control-sm"
                                                    :placeholder="subField.placeholder || ''"
                                                />
                                                <textarea
                                                    v-else-if="subField.type === 'textarea'"
                                                    v-model="item[subField.key]"
                                                    class="form-control form-control-sm"
                                                    :placeholder="subField.placeholder || ''"
                                                    rows="2"
                                                ></textarea>
                                                <div v-else-if="subField.type === 'image'">
                                                    <input
                                                        type="url"
                                                        v-model="item[subField.key]"
                                                        class="form-control form-control-sm mb-1"
                                                        :placeholder="subField.placeholder || 'https://example.com/image.jpg'"
                                                    />
                                                    <img
                                                        v-if="item[subField.key]"
                                                        :src="item[subField.key]"
                                                        class="schema-image-preview mt-1"
                                                        alt="Preview"
                                                        @error="(e) => e.target.style.display='none'"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Image field with URL input and live preview -->
                            <div v-else-if="field.type === 'image'" :class="`col-md-${field.col || 6}`">
                                <label class="form-label fw-semibold">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-danger ms-1">*</span>
                                </label>
                                <input
                                    type="url"
                                    v-model="formData[field.key]"
                                    class="form-control form-control-sm mb-1"
                                    :placeholder="field.placeholder || 'https://example.com/image.jpg'"
                                />
                                <img
                                    v-if="formData[field.key]"
                                    :src="formData[field.key]"
                                    class="schema-image-preview mt-1"
                                    alt="Preview"
                                    @error="(e) => e.target.style.display='none'"
                                />
                            </div>

                            <!-- Tags (comma-separated values) -->
                            <div v-else-if="field.type === 'tags'" :class="`col-md-${field.col || 12}`">
                                <label class="form-label fw-semibold">{{ field.label }}</label>
                                <input
                                    type="text"
                                    v-model="formData[field.key]"
                                    class="form-control form-control-sm"
                                    :placeholder="field.placeholder || 'Comma-separated values'"
                                />
                                <div class="form-text">Separate multiple values with commas</div>
                            </div>

                            <!-- Select -->
                            <div v-else-if="field.type === 'select'" :class="`col-md-${field.col || 6}`">
                                <label class="form-label fw-semibold">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-danger ms-1">*</span>
                                </label>
                                <select v-model="formData[field.key]" class="form-select form-select-sm">
                                    <option value="">-- Select --</option>
                                    <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </div>

                            <!-- Textarea -->
                            <div v-else-if="field.type === 'textarea'" :class="`col-md-${field.col || 12}`">
                                <label class="form-label fw-semibold">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-danger ms-1">*</span>
                                </label>
                                <textarea
                                    v-model="formData[field.key]"
                                    class="form-control form-control-sm"
                                    :placeholder="field.placeholder || ''"
                                    rows="3"
                                ></textarea>
                            </div>

                            <!-- Standard inputs: text, url, date, datetime-local, number, email, tel -->
                            <div v-else :class="`col-md-${field.col || 6}`">
                                <label class="form-label fw-semibold">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-danger ms-1">*</span>
                                </label>
                                <input
                                    :type="field.type"
                                    v-model="formData[field.key]"
                                    class="form-control form-control-sm"
                                    :placeholder="field.placeholder || ''"
                                />
                                <div v-if="field.hint" class="form-text">{{ field.hint }}</div>
                            </div>

                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- JSON-LD Preview -->
        <div v-if="schemaType && schemaPreviewJson" class="schema-builder__preview">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-semibold">JSON-LD Preview</h6>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="copyToClipboard">
                    {{ copied ? '✓ Copied!' : 'Copy' }}
                </button>
            </div>
            <pre class="schema-preview-code"><code>{{ schemaPreviewJson }}</code></pre>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, reactive, onMounted } from 'vue';

const props = defineProps({
    schemajson: { type: String, required: false },
    trackerfieldMap: { type: Array, required: false },
});

// ─── Schema type selector groups ────────────────────────────────────────────

const schemaTypeGroups = [
    {
        label: 'Articles & Content',
        types: [
            { value: 'Article', label: 'Article' },
            { value: 'NewsArticle', label: 'News Article' },
            { value: 'BlogPosting', label: 'Blog Posting' },
        ],
    },
    {
        label: 'Navigation',
        types: [
            { value: 'BreadcrumbList', label: 'Breadcrumb' },
            { value: 'WebSite', label: 'Website / Sitelinks Searchbox' },
        ],
    },
    {
        label: 'Events & Q&A',
        types: [
            { value: 'Event', label: 'Event' },
            { value: 'FAQPage', label: 'FAQ Page' },
        ],
    },
    {
        label: 'Guides & Recipes',
        types: [
            { value: 'HowTo', label: 'How-to' },
            { value: 'Recipe', label: 'Recipe' },
        ],
    },
    {
        label: 'Business & People',
        types: [
            { value: 'LocalBusiness', label: 'Local Business' },
            { value: 'Organization', label: 'Organization' },
            { value: 'Person', label: 'Person' },
            { value: 'JobPosting', label: 'Job Posting' },
        ],
    },
    {
        label: 'Commerce',
        types: [
            { value: 'Product', label: 'Product' },
        ],
    },
    {
        label: 'Media',
        types: [
            { value: 'VideoObject', label: 'Video' },
        ],
    },
];

// ─── Field definitions per schema type ──────────────────────────────────────

const articleFields = [
    { key: 'headline', label: 'Headline', type: 'text', placeholder: 'Article headline', required: true },
    { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Short summary of the article' },
    { key: 'image', label: 'Image', type: 'image', placeholder: 'https://example.com/image.jpg' },
    { key: 'authorName', label: 'Author Name', type: 'text', placeholder: 'John Doe' },
    { key: 'authorUrl', label: 'Author URL', type: 'url', placeholder: 'https://example.com/author' },
    { key: 'publisherName', label: 'Publisher Name', type: 'text', placeholder: 'Publisher Organization' },
    { key: 'publisherLogo', label: 'Publisher Logo', type: 'image', placeholder: 'https://example.com/logo.png' },
    { key: 'datePublished', label: 'Date Published', type: 'date' },
    { key: 'dateModified', label: 'Date Modified', type: 'date' },
    { key: 'mainEntityUrl', label: 'Main Entity Page URL', type: 'url', placeholder: 'https://example.com/article', col: 12 },
];

const schemaDefinitions = {
    Article:     { label: 'Article', fields: articleFields },
    NewsArticle: { label: 'News Article', fields: articleFields },
    BlogPosting: { label: 'Blog Posting', fields: articleFields },

    BreadcrumbList: {
        label: 'Breadcrumb',
        fields: [
            {
                key: 'items', label: 'Breadcrumb Items', type: 'repeatable', itemLabel: 'Item',
                fields: [
                    { key: 'name', label: 'Name', type: 'text', placeholder: 'Home' },
                    { key: 'url', label: 'URL', type: 'url', placeholder: 'https://example.com' },
                ],
            },
        ],
    },

    Event: {
        label: 'Event',
        fields: [
            { key: 'name', label: 'Event Name', type: 'text', placeholder: 'My Event', required: true },
            { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Event description' },
            { key: 'image', label: 'Event Image', type: 'image', placeholder: 'https://example.com/event.jpg' },
            { key: 'startDate', label: 'Start Date/Time', type: 'datetime-local', required: true },
            { key: 'endDate', label: 'End Date/Time', type: 'datetime-local' },
            {
                key: 'eventStatus', label: 'Event Status', type: 'select',
                options: [
                    { value: 'https://schema.org/EventScheduled', label: 'Scheduled' },
                    { value: 'https://schema.org/EventCancelled', label: 'Cancelled' },
                    { value: 'https://schema.org/EventPostponed', label: 'Postponed' },
                    { value: 'https://schema.org/EventRescheduled', label: 'Rescheduled' },
                ],
            },
            {
                key: 'eventAttendanceMode', label: 'Attendance Mode', type: 'select',
                options: [
                    { value: 'https://schema.org/OfflineEventAttendanceMode', label: 'In-Person' },
                    { value: 'https://schema.org/OnlineEventAttendanceMode', label: 'Online' },
                    { value: 'https://schema.org/MixedEventAttendanceMode', label: 'Mixed' },
                ],
            },
            { key: 'locationName', label: 'Location Name', type: 'text', placeholder: 'Venue Name' },
            { key: 'locationAddress', label: 'Location Address', type: 'text', placeholder: '123 Main Street, City' },
            { key: 'organizerName', label: 'Organizer Name', type: 'text', placeholder: 'Organizer' },
            { key: 'organizerUrl', label: 'Organizer URL', type: 'url', placeholder: 'https://example.com/organizer' },
            { key: 'price', label: 'Ticket Price', type: 'text', placeholder: '25.00' },
            { key: 'priceCurrency', label: 'Price Currency', type: 'text', placeholder: 'USD' },
            { key: 'ticketUrl', label: 'Ticket URL', type: 'url', placeholder: 'https://example.com/tickets', col: 12 },
        ],
    },

    FAQPage: {
        label: 'FAQ Page',
        fields: [
            {
                key: 'questions', label: 'Questions & Answers', type: 'repeatable', itemLabel: 'Question',
                fields: [
                    { key: 'question', label: 'Question', type: 'text', placeholder: 'What is...?' },
                    { key: 'answer', label: 'Answer', type: 'textarea', placeholder: 'The answer is...' },
                ],
            },
        ],
    },

    HowTo: {
        label: 'How-to',
        fields: [
            { key: 'name', label: 'Title', type: 'text', placeholder: 'How to make pasta', required: true },
            { key: 'description', label: 'Description', type: 'textarea', placeholder: 'A brief description' },
            { key: 'image', label: 'Image', type: 'image', placeholder: 'https://example.com/image.jpg' },
            { key: 'totalTime', label: 'Total Time (ISO 8601)', type: 'text', placeholder: 'PT30M', hint: 'e.g. PT30M = 30 minutes, PT1H = 1 hour' },
            { key: 'estimatedCost', label: 'Estimated Cost', type: 'text', placeholder: '10' },
            { key: 'estimatedCostCurrency', label: 'Cost Currency', type: 'text', placeholder: 'USD' },
            { key: 'supply', label: 'Supplies', type: 'tags', placeholder: 'Water, Pasta, Salt' },
            { key: 'tool', label: 'Tools', type: 'tags', placeholder: 'Pot, Colander' },
            {
                key: 'steps', label: 'Steps', type: 'repeatable', itemLabel: 'Step',
                fields: [
                    { key: 'name', label: 'Step Name', type: 'text', placeholder: 'Boil water' },
                    { key: 'text', label: 'Instructions', type: 'textarea', placeholder: 'Fill pot with water and bring to a boil' },
                    { key: 'image', label: 'Step Image', type: 'image', placeholder: 'https://example.com/step.jpg' },
                    { key: 'url', label: 'Step URL', type: 'url', placeholder: 'https://example.com/how-to#step-1' },
                ],
            },
        ],
    },

    JobPosting: {
        label: 'Job Posting',
        fields: [
            { key: 'title', label: 'Job Title', type: 'text', placeholder: 'Software Engineer', required: true },
            { key: 'description', label: 'Job Description', type: 'textarea', placeholder: 'We are looking for...', required: true },
            { key: 'datePosted', label: 'Date Posted', type: 'date', required: true },
            { key: 'validThrough', label: 'Valid Through', type: 'datetime-local' },
            { key: 'hiringOrgName', label: 'Hiring Organization', type: 'text', placeholder: 'ACME Corp' },
            { key: 'hiringOrgUrl', label: 'Organization URL', type: 'url', placeholder: 'https://example.com' },
            { key: 'hiringOrgLogo', label: 'Organization Logo', type: 'image', placeholder: 'https://example.com/logo.png' },
            { key: 'jobLocationAddress', label: 'Job Location Address', type: 'text', placeholder: '123 Main St, City, State, Country', col: 12 },
            {
                key: 'remote', label: 'Work Location Type', type: 'select',
                options: [
                    { value: '', label: 'Not specified' },
                    { value: 'TELECOMMUTE', label: 'Fully Remote' },
                ],
            },
            {
                key: 'employmentType', label: 'Employment Type', type: 'select',
                options: [
                    { value: '', label: 'Not specified' },
                    { value: 'FULL_TIME', label: 'Full Time' },
                    { value: 'PART_TIME', label: 'Part Time' },
                    { value: 'CONTRACTOR', label: 'Contractor' },
                    { value: 'TEMPORARY', label: 'Temporary' },
                    { value: 'INTERN', label: 'Intern' },
                    { value: 'VOLUNTEER', label: 'Volunteer' },
                    { value: 'PER_DIEM', label: 'Per Diem' },
                    { value: 'OTHER', label: 'Other' },
                ],
            },
            { key: 'salaryValue', label: 'Salary Value', type: 'text', placeholder: '80000' },
            { key: 'salaryCurrency', label: 'Salary Currency', type: 'text', placeholder: 'USD' },
            {
                key: 'salaryUnit', label: 'Salary Unit', type: 'select',
                options: [
                    { value: '', label: 'Not specified' },
                    { value: 'YEAR', label: 'Per Year' },
                    { value: 'MONTH', label: 'Per Month' },
                    { value: 'WEEK', label: 'Per Week' },
                    { value: 'DAY', label: 'Per Day' },
                    { value: 'HOUR', label: 'Per Hour' },
                ],
            },
        ],
    },

    LocalBusiness: {
        label: 'Local Business',
        fields: [
            { key: 'name', label: 'Business Name', type: 'text', placeholder: 'My Business', required: true },
            { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Business description' },
            { key: 'image', label: 'Image', type: 'image', placeholder: 'https://example.com/business.jpg' },
            { key: 'url', label: 'Website URL', type: 'url', placeholder: 'https://example.com' },
            { key: 'telephone', label: 'Phone Number', type: 'tel', placeholder: '+1-800-555-0100' },
            { key: 'email', label: 'Email', type: 'email', placeholder: 'contact@example.com' },
            { key: 'streetAddress', label: 'Street Address', type: 'text', placeholder: '123 Main St' },
            { key: 'addressLocality', label: 'City', type: 'text', placeholder: 'New York' },
            { key: 'addressRegion', label: 'State / Region', type: 'text', placeholder: 'NY' },
            { key: 'postalCode', label: 'Postal Code', type: 'text', placeholder: '10001' },
            { key: 'addressCountry', label: 'Country Code', type: 'text', placeholder: 'US' },
            { key: 'latitude', label: 'Latitude', type: 'text', placeholder: '40.7128' },
            { key: 'longitude', label: 'Longitude', type: 'text', placeholder: '-74.0060' },
            { key: 'openingHours', label: 'Opening Hours', type: 'tags', placeholder: 'Mo-Fr 09:00-17:00, Sa 10:00-14:00' },
            { key: 'priceRange', label: 'Price Range', type: 'text', placeholder: '$$', col: 3 },
        ],
    },

    Organization: {
        label: 'Organization',
        fields: [
            { key: 'name', label: 'Organization Name', type: 'text', placeholder: 'ACME Corp', required: true },
            { key: 'url', label: 'Website URL', type: 'url', placeholder: 'https://example.com' },
            { key: 'logo', label: 'Logo', type: 'image', placeholder: 'https://example.com/logo.png' },
            { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Organization description' },
            { key: 'foundingDate', label: 'Founding Date', type: 'date' },
            { key: 'email', label: 'Email', type: 'email', placeholder: 'contact@example.com' },
            { key: 'telephone', label: 'Phone Number', type: 'tel', placeholder: '+1-800-555-0100' },
            { key: 'contactType', label: 'Contact Type', type: 'text', placeholder: 'customer service' },
            { key: 'sameAs', label: 'Social Profiles', type: 'tags', placeholder: 'https://twitter.com/example, https://facebook.com/example' },
        ],
    },

    Person: {
        label: 'Person',
        fields: [
            { key: 'name', label: 'Full Name', type: 'text', placeholder: 'Jane Doe', required: true },
            { key: 'url', label: 'Profile URL', type: 'url', placeholder: 'https://example.com/jane' },
            { key: 'image', label: 'Photo', type: 'image', placeholder: 'https://example.com/photo.jpg' },
            { key: 'jobTitle', label: 'Job Title', type: 'text', placeholder: 'Software Engineer' },
            { key: 'worksForName', label: 'Works For (Organization)', type: 'text', placeholder: 'ACME Corp' },
            { key: 'email', label: 'Email', type: 'email', placeholder: 'jane@example.com' },
            { key: 'telephone', label: 'Phone', type: 'tel', placeholder: '+1-800-555-0100' },
            { key: 'description', label: 'Biography', type: 'textarea', placeholder: 'Brief bio' },
            { key: 'sameAs', label: 'Social Profiles', type: 'tags', placeholder: 'https://twitter.com/jane, https://linkedin.com/in/jane' },
        ],
    },

    Product: {
        label: 'Product',
        fields: [
            { key: 'name', label: 'Product Name', type: 'text', placeholder: 'Amazing Widget', required: true },
            { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Product description' },
            { key: 'image', label: 'Product Image', type: 'image', placeholder: 'https://example.com/product.jpg' },
            { key: 'sku', label: 'SKU', type: 'text', placeholder: 'SKU-12345' },
            { key: 'gtin', label: 'GTIN / Barcode', type: 'text', placeholder: '012345678901' },
            { key: 'brand', label: 'Brand Name', type: 'text', placeholder: 'Brand Corp' },
            { key: 'offerUrl', label: 'Product Page URL', type: 'url', placeholder: 'https://example.com/product', col: 12 },
            { key: 'price', label: 'Price', type: 'text', placeholder: '99.99' },
            { key: 'priceCurrency', label: 'Price Currency', type: 'text', placeholder: 'USD' },
            { key: 'priceValidUntil', label: 'Price Valid Until', type: 'date' },
            {
                key: 'availability', label: 'Availability', type: 'select',
                options: [
                    { value: 'https://schema.org/InStock', label: 'In Stock' },
                    { value: 'https://schema.org/OutOfStock', label: 'Out of Stock' },
                    { value: 'https://schema.org/PreOrder', label: 'Pre-Order' },
                    { value: 'https://schema.org/BackOrder', label: 'Back Order' },
                    { value: 'https://schema.org/Discontinued', label: 'Discontinued' },
                ],
            },
            {
                key: 'itemCondition', label: 'Item Condition', type: 'select',
                options: [
                    { value: 'https://schema.org/NewCondition', label: 'New' },
                    { value: 'https://schema.org/UsedCondition', label: 'Used' },
                    { value: 'https://schema.org/RefurbishedCondition', label: 'Refurbished' },
                ],
            },
            { key: 'ratingValue', label: 'Aggregate Rating Value', type: 'number', placeholder: '4.5' },
            { key: 'ratingCount', label: 'Rating Count', type: 'number', placeholder: '128' },
            { key: 'reviewCount', label: 'Review Count', type: 'number', placeholder: '45' },
            {
                key: 'reviews', label: 'Reviews', type: 'repeatable', itemLabel: 'Review',
                fields: [
                    { key: 'reviewBody', label: 'Review Text', type: 'textarea', placeholder: 'Great product!' },
                    { key: 'ratingValue', label: 'Rating (1–5)', type: 'number', placeholder: '5' },
                    { key: 'authorName', label: 'Reviewer Name', type: 'text', placeholder: 'Jane Doe' },
                    { key: 'datePublished', label: 'Date Published', type: 'date' },
                ],
            },
        ],
    },

    Recipe: {
        label: 'Recipe',
        fields: [
            { key: 'name', label: 'Recipe Name', type: 'text', placeholder: 'Chocolate Chip Cookies', required: true },
            { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Delicious homemade cookies...' },
            { key: 'image', label: 'Recipe Image', type: 'image', placeholder: 'https://example.com/recipe.jpg' },
            { key: 'authorName', label: 'Author Name', type: 'text', placeholder: 'Jane Doe' },
            { key: 'datePublished', label: 'Date Published', type: 'date' },
            { key: 'prepTime', label: 'Prep Time (ISO 8601)', type: 'text', placeholder: 'PT15M', hint: 'e.g. PT15M = 15 minutes' },
            { key: 'cookTime', label: 'Cook Time (ISO 8601)', type: 'text', placeholder: 'PT1H' },
            { key: 'totalTime', label: 'Total Time (ISO 8601)', type: 'text', placeholder: 'PT1H15M' },
            { key: 'recipeYield', label: 'Recipe Yield', type: 'text', placeholder: '24 cookies' },
            { key: 'recipeCategory', label: 'Category', type: 'text', placeholder: 'Dessert' },
            { key: 'recipeCuisine', label: 'Cuisine', type: 'text', placeholder: 'American' },
            { key: 'keywords', label: 'Keywords', type: 'tags', placeholder: 'chocolate, cookies, dessert' },
            { key: 'calories', label: 'Calories', type: 'text', placeholder: '250 calories' },
            { key: 'recipeIngredient', label: 'Ingredients (one per line)', type: 'textarea', placeholder: '2 cups flour\n1 cup sugar\n1/2 tsp salt' },
            {
                key: 'recipeInstructions', label: 'Instruction Steps', type: 'repeatable', itemLabel: 'Step',
                fields: [
                    { key: 'name', label: 'Step Name', type: 'text', placeholder: 'Mix dry ingredients' },
                    { key: 'text', label: 'Instructions', type: 'textarea', placeholder: 'Combine flour, baking soda...' },
                    { key: 'image', label: 'Step Image', type: 'image', placeholder: 'https://example.com/step.jpg' },
                ],
            },
        ],
    },

    VideoObject: {
        label: 'Video',
        fields: [
            { key: 'name', label: 'Video Title', type: 'text', placeholder: 'My Video Title', required: true },
            { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Video description' },
            { key: 'thumbnailUrl', label: 'Thumbnail', type: 'image', placeholder: 'https://example.com/thumbnail.jpg', required: true },
            { key: 'uploadDate', label: 'Upload Date', type: 'date', required: true },
            { key: 'duration', label: 'Duration (ISO 8601)', type: 'text', placeholder: 'PT5M30S', hint: 'e.g. PT5M30S = 5 min 30 sec' },
            { key: 'contentUrl', label: 'Content URL (direct file)', type: 'url', placeholder: 'https://example.com/video.mp4' },
            { key: 'embedUrl', label: 'Embed URL', type: 'url', placeholder: 'https://www.youtube.com/embed/...' },
            { key: 'publisher', label: 'Publisher / Channel Name', type: 'text', placeholder: 'My Channel' },
            { key: 'interactionCount', label: 'View Count', type: 'number', placeholder: '10000' },
        ],
    },

    WebSite: {
        label: 'Website / Sitelinks Searchbox',
        fields: [
            { key: 'name', label: 'Website Name', type: 'text', placeholder: 'My Website', required: true },
            { key: 'url', label: 'Website URL', type: 'url', placeholder: 'https://example.com', required: true },
            { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Website description' },
            { key: 'alternateName', label: 'Alternate Name', type: 'text', placeholder: 'Short name or abbreviation' },
            {
                key: 'searchUrlTemplate', label: 'Search URL Template', type: 'url',
                placeholder: 'https://example.com/search?q={search_term_string}',
                hint: 'Include {search_term_string} as the search placeholder to enable Sitelinks Searchbox',
                col: 12,
            },
        ],
    },
};

// ─── JSON-LD builders per schema type ───────────────────────────────────────

function buildArticle(data, type = 'Article') {
    const schema = { '@context': 'https://schema.org', '@type': type };
    if (data.headline) schema.headline = data.headline;
    if (data.description) schema.description = data.description;
    if (data.image) schema.image = data.image;
    if (data.authorName) {
        schema.author = { '@type': 'Person', name: data.authorName };
        if (data.authorUrl) schema.author.url = data.authorUrl;
    }
    if (data.publisherName) {
        schema.publisher = { '@type': 'Organization', name: data.publisherName };
        if (data.publisherLogo) schema.publisher.logo = { '@type': 'ImageObject', url: data.publisherLogo };
    }
    if (data.datePublished) schema.datePublished = data.datePublished;
    if (data.dateModified) schema.dateModified = data.dateModified;
    if (data.mainEntityUrl) schema.mainEntityOfPage = { '@type': 'WebPage', '@id': data.mainEntityUrl };
    return schema;
}

const schemaJsonBuilders = {
    Article:     (data) => buildArticle(data, 'Article'),
    NewsArticle: (data) => buildArticle(data, 'NewsArticle'),
    BlogPosting: (data) => buildArticle(data, 'BlogPosting'),

    BreadcrumbList: (data) => ({
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: (data.items || [])
            .filter((item) => item.name || item.url)
            .map((item, i) => ({ '@type': 'ListItem', position: i + 1, name: item.name, item: item.url })),
    }),

    Event: (data) => {
        const schema = { '@context': 'https://schema.org', '@type': 'Event' };
        if (data.name) schema.name = data.name;
        if (data.description) schema.description = data.description;
        if (data.image) schema.image = data.image;
        if (data.startDate) schema.startDate = data.startDate;
        if (data.endDate) schema.endDate = data.endDate;
        if (data.eventStatus) schema.eventStatus = data.eventStatus;
        if (data.eventAttendanceMode) schema.eventAttendanceMode = data.eventAttendanceMode;
        if (data.locationName || data.locationAddress) {
            schema.location = { '@type': 'Place' };
            if (data.locationName) schema.location.name = data.locationName;
            if (data.locationAddress) schema.location.address = data.locationAddress;
        }
        if (data.organizerName) {
            schema.organizer = { '@type': 'Organization', name: data.organizerName };
            if (data.organizerUrl) schema.organizer.url = data.organizerUrl;
        }
        if (data.price || data.priceCurrency || data.ticketUrl) {
            schema.offers = { '@type': 'Offer' };
            if (data.price) schema.offers.price = data.price;
            if (data.priceCurrency) schema.offers.priceCurrency = data.priceCurrency;
            if (data.ticketUrl) schema.offers.url = data.ticketUrl;
        }
        return schema;
    },

    FAQPage: (data) => ({
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: (data.questions || [])
            .filter((q) => q.question || q.answer)
            .map((q) => ({
                '@type': 'Question',
                name: q.question,
                acceptedAnswer: { '@type': 'Answer', text: q.answer },
            })),
    }),

    HowTo: (data) => {
        const schema = { '@context': 'https://schema.org', '@type': 'HowTo' };
        if (data.name) schema.name = data.name;
        if (data.description) schema.description = data.description;
        if (data.image) schema.image = data.image;
        if (data.totalTime) schema.totalTime = data.totalTime;
        if (data.estimatedCost) {
            schema.estimatedCost = {
                '@type': 'MonetaryAmount',
                currency: data.estimatedCostCurrency || 'USD',
                value: data.estimatedCost,
            };
        }
        if (data.supply) {
            schema.supply = data.supply.split(',').map((s) => ({ '@type': 'HowToSupply', name: s.trim() })).filter((s) => s.name);
        }
        if (data.tool) {
            schema.tool = data.tool.split(',').map((t) => ({ '@type': 'HowToTool', name: t.trim() })).filter((t) => t.name);
        }
        if (data.steps && data.steps.length) {
            schema.step = data.steps
                .filter((s) => s.text)
                .map((s) => {
                    const step = { '@type': 'HowToStep', text: s.text };
                    if (s.name) step.name = s.name;
                    if (s.image) step.image = s.image;
                    if (s.url) step.url = s.url;
                    return step;
                });
        }
        return schema;
    },

    JobPosting: (data) => {
        const schema = { '@context': 'https://schema.org', '@type': 'JobPosting' };
        if (data.title) schema.title = data.title;
        if (data.description) schema.description = data.description;
        if (data.datePosted) schema.datePosted = data.datePosted;
        if (data.validThrough) schema.validThrough = data.validThrough;
        if (data.hiringOrgName) {
            schema.hiringOrganization = { '@type': 'Organization', name: data.hiringOrgName };
            if (data.hiringOrgUrl) schema.hiringOrganization.sameAs = data.hiringOrgUrl;
            if (data.hiringOrgLogo) schema.hiringOrganization.logo = data.hiringOrgLogo;
        }
        if (data.jobLocationAddress) {
            schema.jobLocation = { '@type': 'Place', address: data.jobLocationAddress };
        }
        if (data.remote === 'TELECOMMUTE') schema.jobLocationType = 'TELECOMMUTE';
        if (data.employmentType) schema.employmentType = data.employmentType;
        if (data.salaryValue) {
            schema.baseSalary = {
                '@type': 'MonetaryAmount',
                currency: data.salaryCurrency || 'USD',
                value: { '@type': 'QuantitativeValue', value: data.salaryValue, unitText: data.salaryUnit || 'YEAR' },
            };
        }
        return schema;
    },

    LocalBusiness: (data) => {
        const schema = { '@context': 'https://schema.org', '@type': 'LocalBusiness' };
        if (data.name) schema.name = data.name;
        if (data.description) schema.description = data.description;
        if (data.image) schema.image = data.image;
        if (data.url) schema.url = data.url;
        if (data.telephone) schema.telephone = data.telephone;
        if (data.email) schema.email = data.email;
        if (data.streetAddress || data.addressLocality || data.postalCode) {
            schema.address = { '@type': 'PostalAddress' };
            if (data.streetAddress) schema.address.streetAddress = data.streetAddress;
            if (data.addressLocality) schema.address.addressLocality = data.addressLocality;
            if (data.addressRegion) schema.address.addressRegion = data.addressRegion;
            if (data.postalCode) schema.address.postalCode = data.postalCode;
            if (data.addressCountry) schema.address.addressCountry = data.addressCountry;
        }
        if (data.latitude && data.longitude) {
            schema.geo = { '@type': 'GeoCoordinates', latitude: data.latitude, longitude: data.longitude };
        }
        if (data.openingHours) {
            schema.openingHours = data.openingHours.split(',').map((s) => s.trim()).filter(Boolean);
        }
        if (data.priceRange) schema.priceRange = data.priceRange;
        return schema;
    },

    Organization: (data) => {
        const schema = { '@context': 'https://schema.org', '@type': 'Organization' };
        if (data.name) schema.name = data.name;
        if (data.url) schema.url = data.url;
        if (data.logo) schema.logo = data.logo;
        if (data.description) schema.description = data.description;
        if (data.foundingDate) schema.foundingDate = data.foundingDate;
        if (data.email || data.telephone) {
            schema.contactPoint = { '@type': 'ContactPoint' };
            if (data.email) schema.contactPoint.email = data.email;
            if (data.telephone) schema.contactPoint.telephone = data.telephone;
            if (data.contactType) schema.contactPoint.contactType = data.contactType;
        }
        if (data.sameAs) schema.sameAs = data.sameAs.split(',').map((s) => s.trim()).filter(Boolean);
        return schema;
    },

    Person: (data) => {
        const schema = { '@context': 'https://schema.org', '@type': 'Person' };
        if (data.name) schema.name = data.name;
        if (data.url) schema.url = data.url;
        if (data.image) schema.image = data.image;
        if (data.jobTitle) schema.jobTitle = data.jobTitle;
        if (data.worksForName) schema.worksFor = { '@type': 'Organization', name: data.worksForName };
        if (data.email) schema.email = data.email;
        if (data.telephone) schema.telephone = data.telephone;
        if (data.description) schema.description = data.description;
        if (data.sameAs) schema.sameAs = data.sameAs.split(',').map((s) => s.trim()).filter(Boolean);
        return schema;
    },

    Product: (data) => {
        const schema = { '@context': 'https://schema.org', '@type': 'Product' };
        if (data.name) schema.name = data.name;
        if (data.description) schema.description = data.description;
        if (data.image) schema.image = data.image;
        if (data.sku) schema.sku = data.sku;
        if (data.gtin) schema.gtin = data.gtin;
        if (data.brand) schema.brand = { '@type': 'Brand', name: data.brand };
        if (data.price || data.priceCurrency || data.offerUrl || data.availability || data.itemCondition) {
            schema.offers = { '@type': 'Offer' };
            if (data.offerUrl) schema.offers.url = data.offerUrl;
            if (data.price) schema.offers.price = data.price;
            if (data.priceCurrency) schema.offers.priceCurrency = data.priceCurrency;
            if (data.priceValidUntil) schema.offers.priceValidUntil = data.priceValidUntil;
            if (data.availability) schema.offers.availability = data.availability;
            if (data.itemCondition) schema.offers.itemCondition = data.itemCondition;
        }
        if (data.ratingValue || data.ratingCount) {
            schema.aggregateRating = { '@type': 'AggregateRating' };
            if (data.ratingValue) schema.aggregateRating.ratingValue = data.ratingValue;
            if (data.ratingCount) schema.aggregateRating.ratingCount = data.ratingCount;
            if (data.reviewCount) schema.aggregateRating.reviewCount = data.reviewCount;
        }
        if (data.reviews && data.reviews.length) {
            schema.review = data.reviews
                .filter((r) => r.reviewBody || r.ratingValue)
                .map((r) => ({
                    '@type': 'Review',
                    reviewBody: r.reviewBody,
                    reviewRating: { '@type': 'Rating', ratingValue: r.ratingValue },
                    author: { '@type': 'Person', name: r.authorName },
                    datePublished: r.datePublished,
                }));
        }
        return schema;
    },

    Recipe: (data) => {
        const schema = { '@context': 'https://schema.org', '@type': 'Recipe' };
        if (data.name) schema.name = data.name;
        if (data.description) schema.description = data.description;
        if (data.image) schema.image = data.image;
        if (data.authorName) schema.author = { '@type': 'Person', name: data.authorName };
        if (data.datePublished) schema.datePublished = data.datePublished;
        if (data.prepTime) schema.prepTime = data.prepTime;
        if (data.cookTime) schema.cookTime = data.cookTime;
        if (data.totalTime) schema.totalTime = data.totalTime;
        if (data.recipeYield) schema.recipeYield = data.recipeYield;
        if (data.recipeCategory) schema.recipeCategory = data.recipeCategory;
        if (data.recipeCuisine) schema.recipeCuisine = data.recipeCuisine;
        if (data.keywords) schema.keywords = data.keywords;
        if (data.calories) schema.nutrition = { '@type': 'NutritionInformation', calories: data.calories };
        if (data.recipeIngredient) {
            schema.recipeIngredient = data.recipeIngredient.split('\n').map((s) => s.trim()).filter(Boolean);
        }
        if (data.recipeInstructions && data.recipeInstructions.length) {
            schema.recipeInstructions = data.recipeInstructions
                .filter((s) => s.text)
                .map((s) => {
                    const step = { '@type': 'HowToStep', text: s.text };
                    if (s.name) step.name = s.name;
                    if (s.image) step.image = s.image;
                    return step;
                });
        }
        return schema;
    },

    VideoObject: (data) => {
        const schema = { '@context': 'https://schema.org', '@type': 'VideoObject' };
        if (data.name) schema.name = data.name;
        if (data.description) schema.description = data.description;
        if (data.thumbnailUrl) schema.thumbnailUrl = data.thumbnailUrl;
        if (data.uploadDate) schema.uploadDate = data.uploadDate;
        if (data.duration) schema.duration = data.duration;
        if (data.contentUrl) schema.contentUrl = data.contentUrl;
        if (data.embedUrl) schema.embedUrl = data.embedUrl;
        if (data.publisher) schema.publisher = { '@type': 'Organization', name: data.publisher };
        if (data.interactionCount) {
            schema.interactionStatistic = {
                '@type': 'InteractionCounter',
                interactionType: { '@type': 'WatchAction' },
                userInteractionCount: data.interactionCount,
            };
        }
        return schema;
    },

    WebSite: (data) => {
        const schema = { '@context': 'https://schema.org', '@type': 'WebSite' };
        if (data.name) schema.name = data.name;
        if (data.url) schema.url = data.url;
        if (data.description) schema.description = data.description;
        if (data.alternateName) schema.alternateName = data.alternateName;
        if (data.searchUrlTemplate) {
            schema.potentialAction = {
                '@type': 'SearchAction',
                target: { '@type': 'EntryPoint', urlTemplate: data.searchUrlTemplate },
                'query-input': 'required name=search_term_string',
            };
        }
        return schema;
    },
};

// ─── State ───────────────────────────────────────────────────────────────────

const schemaType = ref('');
const formData = reactive({});
const copied = ref(false);

const currentSchema = computed(() => schemaDefinitions[schemaType.value] || null);

const schemaJson = computed(() => {
    if (!schemaType.value || !schemaJsonBuilders[schemaType.value]) return null;
    return schemaJsonBuilders[schemaType.value](formData);
});

const schemaPreviewJson = computed(() => {
    if (!schemaJson.value) return '';
    return JSON.stringify(schemaJson.value, null, 2);
});

// ─── Methods ─────────────────────────────────────────────────────────────────

function resetForm() {
    // Clear existing keys
    Object.keys(formData).forEach((k) => delete formData[k]);
    const schema = schemaDefinitions[schemaType.value];
    if (!schema) return;
    schema.fields.forEach((field) => {
        formData[field.key] = field.type === 'repeatable' ? [] : '';
    });
}

function addRepeatableItem(key, fields) {
    if (!Array.isArray(formData[key])) formData[key] = [];
    const item = {};
    fields.forEach((f) => { item[f.key] = ''; });
    formData[key].push(item);
}

function removeRepeatableItem(key, index) {
    formData[key].splice(index, 1);
}

// ─── Tracker field insertion ─────────────────────────────────────────────────

const lastFocusedEl = ref(null);

function handleFocusIn(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        lastFocusedEl.value = e.target;
    }
}

function insertAtCursor(fieldId) {
    const el = lastFocusedEl.value;
    const token = `{{${fieldId}}}`;

    if (!el) return;

    const start = el.selectionStart ?? el.value.length;
    const end   = el.selectionEnd   ?? el.value.length;
    const newValue = el.value.slice(0, start) + token + el.value.slice(end);

    // Update DOM value
    el.value = newValue;

    // Trigger Vue v-model sync
    el.dispatchEvent(new Event('input', { bubbles: true }));

    // Restore cursor after inserted token
    const cursor = start + token.length;
    el.setSelectionRange(cursor, cursor);
    el.focus();
}

function copyToClipboard() {
    navigator.clipboard.writeText(schemaPreviewJson.value).then(() => {
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    });
}

// ─── Emit custom JS event to document on schema change ───────────────────────

watch(schemaJson, (newVal) => {
    document.dispatchEvent(
        new CustomEvent('schema-builder:update', {
            detail: {
                schema: newVal ?? null,
                schemaType: schemaType.value,
                schemaJson: newVal ? JSON.stringify(newVal) : '',
            },
            bubbles: true,
        })
    );
}, { deep: true });

// ─── Hydrate from schemajson prop ────────────────────────────────────────────

const schemaHydrators = {
    Article:     hydrateArticle,
    NewsArticle: hydrateArticle,
    BlogPosting: hydrateArticle,

    BreadcrumbList(jsonld, form) {
        form.items = (jsonld.itemListElement || []).map((item) => ({ name: item.name || '', url: item.item || '' }));
    },

    Event(jsonld, form) {
        copyDirect(jsonld, form, ['name', 'description', 'image', 'startDate', 'endDate', 'eventStatus', 'eventAttendanceMode']);
        form.locationName    = jsonld.location?.name || '';
        form.locationAddress = jsonld.location?.address || '';
        form.organizerName   = jsonld.organizer?.name || '';
        form.organizerUrl    = jsonld.organizer?.url || '';
        form.price           = jsonld.offers?.price || '';
        form.priceCurrency   = jsonld.offers?.priceCurrency || '';
        form.ticketUrl       = jsonld.offers?.url || '';
    },

    FAQPage(jsonld, form) {
        form.questions = (jsonld.mainEntity || []).map((question) => ({
            question: question.name || '',
            answer:   question.acceptedAnswer?.text || '',
        }));
    },

    HowTo(jsonld, form) {
        copyDirect(jsonld, form, ['name', 'description', 'image', 'totalTime']);
        form.estimatedCost         = jsonld.estimatedCost?.value || '';
        form.estimatedCostCurrency = jsonld.estimatedCost?.currency || '';
        form.supply = (jsonld.supply || []).map((item) => item.name).join(', ');
        form.tool   = (jsonld.tool   || []).map((item) => item.name).join(', ');
        form.steps  = (jsonld.step   || []).map((step) => ({ name: step.name || '', text: step.text || '', image: step.image || '', url: step.url || '' }));
    },

    JobPosting(jsonld, form) {
        copyDirect(jsonld, form, ['title', 'description', 'datePosted', 'validThrough', 'employmentType']);
        form.hiringOrgName     = jsonld.hiringOrganization?.name  || '';
        form.hiringOrgUrl      = jsonld.hiringOrganization?.sameAs || '';
        form.hiringOrgLogo     = jsonld.hiringOrganization?.logo   || '';
        form.jobLocationAddress = jsonld.jobLocation?.address || '';
        form.remote            = jsonld.jobLocationType || '';
        form.salaryValue       = jsonld.baseSalary?.value?.value    || '';
        form.salaryCurrency    = jsonld.baseSalary?.currency        || '';
        form.salaryUnit        = jsonld.baseSalary?.value?.unitText || '';
    },

    LocalBusiness(jsonld, form) {
        copyDirect(jsonld, form, ['name', 'description', 'image', 'url', 'telephone', 'email', 'priceRange']);
        form.streetAddress   = jsonld.address?.streetAddress   || '';
        form.addressLocality = jsonld.address?.addressLocality || '';
        form.addressRegion   = jsonld.address?.addressRegion   || '';
        form.postalCode      = jsonld.address?.postalCode      || '';
        form.addressCountry  = jsonld.address?.addressCountry  || '';
        form.latitude        = jsonld.geo?.latitude  || '';
        form.longitude       = jsonld.geo?.longitude || '';
        form.openingHours    = Array.isArray(jsonld.openingHours) ? jsonld.openingHours.join(', ') : (jsonld.openingHours || '');
    },

    Organization(jsonld, form) {
        copyDirect(jsonld, form, ['name', 'url', 'logo', 'description', 'foundingDate']);
        form.email       = jsonld.contactPoint?.email       || '';
        form.telephone   = jsonld.contactPoint?.telephone   || '';
        form.contactType = jsonld.contactPoint?.contactType || '';
        form.sameAs      = Array.isArray(jsonld.sameAs) ? jsonld.sameAs.join(', ') : (jsonld.sameAs || '');
    },

    Person(jsonld, form) {
        copyDirect(jsonld, form, ['name', 'url', 'image', 'jobTitle', 'email', 'telephone', 'description']);
        form.worksForName = jsonld.worksFor?.name || '';
        form.sameAs       = Array.isArray(jsonld.sameAs) ? jsonld.sameAs.join(', ') : (jsonld.sameAs || '');
    },

    Product(jsonld, form) {
        copyDirect(jsonld, form, ['name', 'description', 'image', 'sku', 'gtin']);
        form.brand           = jsonld.brand?.name || '';
        form.offerUrl        = jsonld.offers?.url            || '';
        form.price           = jsonld.offers?.price          || '';
        form.priceCurrency   = jsonld.offers?.priceCurrency  || '';
        form.priceValidUntil = jsonld.offers?.priceValidUntil || '';
        form.availability    = jsonld.offers?.availability   || '';
        form.itemCondition   = jsonld.offers?.itemCondition  || '';
        form.ratingValue     = jsonld.aggregateRating?.ratingValue  || '';
        form.ratingCount     = jsonld.aggregateRating?.ratingCount  || '';
        form.reviewCount     = jsonld.aggregateRating?.reviewCount  || '';
        form.reviews = (jsonld.review || []).map((review) => ({
            reviewBody:    review.reviewBody || '',
            ratingValue:   review.reviewRating?.ratingValue || '',
            authorName:    review.author?.name || '',
            datePublished: review.datePublished || '',
        }));
    },

    Recipe(jsonld, form) {
        copyDirect(jsonld, form, ['name', 'description', 'image', 'datePublished', 'prepTime', 'cookTime', 'totalTime',
                                  'recipeYield', 'recipeCategory', 'recipeCuisine', 'keywords']);
        form.authorName        = jsonld.author?.name || '';
        form.calories          = jsonld.nutrition?.calories || '';
        form.recipeIngredient  = Array.isArray(jsonld.recipeIngredient) ? jsonld.recipeIngredient.join('\n') : (jsonld.recipeIngredient || '');
        form.recipeInstructions = (jsonld.recipeInstructions || []).map((step) => ({
            name: step.name || '', text: step.text || '', image: step.image || '',
        }));
    },

    VideoObject(jsonld, form) {
        copyDirect(jsonld, form, ['name', 'description', 'thumbnailUrl', 'uploadDate', 'duration', 'contentUrl', 'embedUrl']);
        form.publisher        = jsonld.publisher?.name || '';
        form.interactionCount = jsonld.interactionStatistic?.userInteractionCount || '';
    },

    WebSite(jsonld, form) {
        copyDirect(jsonld, form, ['name', 'url', 'description', 'alternateName']);
        form.searchUrlTemplate = jsonld.potentialAction?.target?.urlTemplate || jsonld.potentialAction?.target || '';
    },
};

function copyDirect(jsonld, form, keys) {
    keys.forEach((key) => { if (jsonld[key] !== undefined) form[key] = jsonld[key]; });
}

function hydrateArticle(jsonld, form) {
    copyDirect(jsonld, form, ['headline', 'description', 'image', 'datePublished', 'dateModified']);
    form.authorName    = jsonld.author?.name    || '';
    form.authorUrl     = jsonld.author?.url     || '';
    form.publisherName = jsonld.publisher?.name || '';
    form.publisherLogo = jsonld.publisher?.logo?.url || (typeof jsonld.publisher?.logo === 'string' ? jsonld.publisher.logo : '');
    form.mainEntityUrl = jsonld.mainEntityOfPage?.['@id'] || '';
}

onMounted(() => {
    if (props.schemajson) {
        try {
            const parsed = JSON.parse(props.schemajson);
            const type = parsed['@type'];
            if (type && schemaDefinitions[type]) {
                schemaType.value = type;
                resetForm();
                schemaHydrators[type]?.(parsed, formData);
            }
        } catch {
            // Invalid JSON prop — ignore
        }
    }
});
</script>

<style scoped>
.schema-image-preview {
    display: block;
    max-width: 140px;
    max-height: 90px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    object-fit: cover;
}

.schema-preview-code {
    background: #1a1a2e;
    color: #a8d8a8;
    padding: 1rem 1.25rem;
    border-radius: 6px;
    font-size: 0.78rem;
    line-height: 1.6;
    max-height: 480px;
    overflow-y: auto;
    white-space: pre;
    word-break: normal;
}

.schema-preview-code code {
    font-family: monospace;
    color: inherit;
    background: none;
    padding: 0;
}
</style>