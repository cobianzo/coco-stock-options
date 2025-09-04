/**
 * LocalStorage Manager - CRUD operations for managing data in localStorage
 */

export class LocalStorageManager {
  /**
   * Save data to localStorage
   * @param key - Storage key
   * @param value - Data to store
   */
  static save<T>(key: string, value: T): void {
    try {
      const serializedValue = JSON.stringify(value);
      localStorage.setItem(key, serializedValue);
    } catch (error) {
      console.error('Error saving to localStorage:', error);
    }
  }

  /**
   * Retrieve data from localStorage
   * @param key - Storage key
   * @returns Stored data or null if not found
   */
  static load<T>(key: string): T | null {
    try {
      const item = localStorage.getItem(key);
      return item ? JSON.parse(item) : null;
    } catch (error) {
      console.error('Error loading from localStorage:', error);
      return null;
    }
  }

  /**
   * Update existing data in localStorage
   * @param key - Storage key
   * @param updateFn - Update function that receives current value and returns new value
   */
  static update<T>(key: string, updateFn: (currentValue: T | null) => T): void {
    try {
      const currentValue = this.load<T>(key);
      const newValue = updateFn(currentValue);
      this.save(key, newValue);
    } catch (error) {
      console.error('Error updating localStorage:', error);
    }
  }

  /**
   * Delete data from localStorage
   * @param key - Storage key
   */
  static delete(key: string): void {
    try {
      localStorage.removeItem(key);
    } catch (error) {
      console.error('Error deleting from localStorage:', error);
    }
  }

  /**
   * Check if key exists in localStorage
   * @param key - Storage key
   */
  static exists(key: string): boolean {
    return localStorage.getItem(key) !== null;
  }

  /**
   * Clear all data from localStorage
   */
  static clear(): void {
    try {
      localStorage.clear();
    } catch (error) {
      console.error('Error clearing localStorage:', error);
    }
  }
}
