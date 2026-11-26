<?php

require_once __DIR__ . '/functions.php';

function requireCustomer() {
    requireRole('customer');
}

function requireStaff() {
    requireRole('staff');
}

function requireAdmin() {
    requireRole('admin');
}

function requireStaffOrAdmin() {
    requireRole(['staff', 'admin']);
}