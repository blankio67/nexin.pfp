#include <iostream>
#include <fstream>
#include <string>

int main() {
    std::string username, bio, color, avatar;
    std::cout << "Enter username: ";
    std::cin >> username;
    std::cin.ignore();
    std::cout << "Enter bio: ";
    std::getline(std::cin, bio);
    std::cout << "Enter color (hex, e.g. #00bfff): ";
    std::cin >> color;
    std::cout << "Enter avatar URL: ";
    std::cin >> avatar;

    std::ofstream file("bios.json", std::ios::app);
    if (file.is_open()) {
        file << ",\n  \"" << username << "\": {\n";
        file << "    \"name\": \"" << username << "\",\n";
        file << "    \"bio\": \"" << bio << "\",\n";
        file << "    \"color\": \"" << color << "\",\n";
        file << "    \"avatar\": \"" << avatar << "\"\n";
        file << "  }";
        file.close();
        std::cout << "✅ Added bio for " << username << "!\n";
    } else {
        std::cerr << "Error opening bios.json\n";
    }

    return 0;
}
