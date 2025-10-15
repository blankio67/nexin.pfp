#include <iostream>
#include <fstream>
#include <string>

int main() {
    std::ofstream file("data.txt");
    std::string name = "cd";
    std::string bio = "C++ developer and Minecraft server creator.";
    int projects = 7;

    if (file.is_open()) {
        file << "Name: " << name << "\n";
        file << "Bio: " << bio << "\n";
        file << "Projects: " << projects << "\n";
        file.close();
        std::cout << "Profile data written successfully.\n";
    } else {
        std::cerr << "Error writing file.\n";
    }

    return 0;
}
